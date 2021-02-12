<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class BurndownHelper
{
    public static function computeBurndown(
        array $selectedProject,
        array $selectedSprint,
        \DateTime $startDate,
        \DateTime $endDate,
        array $initialIssuesForSprint,
        array $doneStatuses,
        JiraClient $jiraClient
    ): array {
        $initialPoints = 0;
        array_map(
            function (array $issue) use (&$initialPoints, $startDate) {
                $initialPoints += $issue['fields']['customfield_10028'] ?: 0;
            }, $initialIssuesForSprint
        );

        $idealPoints = [
            'type'            => 'line',
            'label'           => 'Ideal burndown',
            'yAxisID'         => 'normal_axis',
            'fill'            => false,
            'backgroundColor' => 'rgba(192,85,75,0.5)',
            'borderColor'     => 'rgb(192,85,75)',
            'borderWidth'     => 2,
            'pointRadius'     => 0,
            'data'            => [],
        ];
        $zeroPoints = [
            'type'        => 'line',
            'label'       => 'zero',
            'yAxisID'     => 'normal_axis',
            'fill'        => false,
            'legend'      => [
                'display' => false,
                'enabled' => false,
            ],
            'borderColor' => 'rgb(255,255,255)',
            'borderWidth' => 1,
            'pointRadius' => 0,
            'data'        => [],
        ];
        $realPoints = [
            'type'            => 'line',
            'label'           => 'Burndown',
            'yAxisID'         => 'normal_axis',
            'fill'            => false,
            'backgroundColor' => 'rgba(27,156,215,0.5)',
            'borderColor'     => 'rgb(27,156,215)',
            'borderWidth'     => 3,
            'lineTension'     => .1,
            'data'            => [],
        ];
        $dailyPoints = [
            'type'            => 'bar',
            'label'           => 'Done',
            'yAxisID'         => 'normal_axis',
            'backgroundColor' => 'rgba(111,171,53,0.5)',
            'borderColor'     => 'rgb(111,171,53)',
            'borderWidth'     => 2,
            'data'            => [],
        ];

        $diffDays = $endDate->diff($startDate)->days;
        $chartLabels = [];
        $now = new \DateTime();
        $increment = 0;
        $rincrement = $diffDays;
        for ($dayOfSprint = $startDate; $dayOfSprint <= $endDate; $dayOfSprint->modify('+1 day')) {
            $chartLabels[] = $dayOfSprint->format('Y-m-d');
            $pointsForTheDay = 0;
            array_map(
                function (array $issue) use (&$pointsForTheDay, $dayOfSprint) {
                    $pointsForTheDay += $issue['fields']['customfield_10028'] ?: 0;
                }, $jiraClient->getDailyDoneIssues(
                $selectedProject['id'], $selectedSprint['name'], $dayOfSprint, $doneStatuses
            )
            );
            $dailyPoints['data'][] = ['x' => $dayOfSprint->format('Y-m-d'), 'y' => $pointsForTheDay];
            $idealPoints['data'][] = [
                'x' => $dayOfSprint->format('Y-m-d'),
                'y' => $rincrement * $initialPoints / $diffDays,
            ];
            $zeroPoints['data'][] = ['x' => $dayOfSprint->format('Y-m-d'), 'y' => 0];
            $realPoints['data'][] = ($dayOfSprint <= $now)
                ? [
                    'x' => $dayOfSprint->format('Y-m-d'),
                    'y' => (0 === $increment ? $initialPoints : $realPoints['data'][$increment - 1]['y']) - $dailyPoints['data'][$increment]['y'],
                ] : [
                    'x' => $dayOfSprint->format('Y-m-d'),
                    'y' => null,
                ];
            $increment++;
            $rincrement--;
        }

        return [
            'labels'   => $chartLabels,
            'datasets' => [
                'ideal' => $idealPoints,
                'daily' => $dailyPoints,
                'real'  => $realPoints,
                'zero'  => $zeroPoints,
            ],
        ];
    }

    public static function resolveSprint(Request $request, array $sprints): array
    {
        $selectedSprint = [];
        array_map(
            function (array $sprintItem) use (&$selectedSprint, $request) {
                $sprints[] = $sprintItem;
                if ((int)$request->attributes->get('sprintId') === $sprintItem['id']) {
                    $selectedSprint = $sprintItem;
                }
            }, $sprints
        );

        return $selectedSprint;
    }

    public static function resolveProject(Request $request, array $projects): array
    {
        $selectedProject = [];
        array_map(
            function (array $projectItem) use (&$selectedProject, $request) {
                $projects[] = $projectItem;
                if ($request->attributes->get('projectId') === $projectItem['id']) {
                    $selectedProject = $projectItem;
                }
            }, $projects
        );

        return $selectedProject;
    }

    private static function isWorked(int $timestamp): bool
    {
        $year = date('Y');
        $easterDate = easter_date($year);
        $daysOff = [];
        $daysOff[] = date("Y-m-d", strtotime("+1 day", $easterDate));// Paques
        $daysOff[] = date("Y-m-d", strtotime("+39 day", $easterDate)); // Ascenssion
        $daysOff[] = date("Y-m-d", strtotime("+50 day", $easterDate));// Pentecotes
        $daysOff[] = date("Y-m-d", strtotime($year . "-01-01"));// 1er janvier
        $daysOff[] = date("Y-m-d", strtotime($year . "-05-01"));// Fete du travail
        $daysOff[] = date("Y-m-d", strtotime($year . "-05-08"));// Victoire des allies
        $daysOff[] = date("Y-m-d", strtotime($year . "-07-14"));// Fete nationale
        $daysOff[] = date("Y-m-d", strtotime($year . "-08-15"));// Assomption
        $daysOff[] = date("Y-m-d", strtotime($year . "-11-01"));// Toussaint
        $daysOff[] = date("Y-m-d", strtotime($year . "-11-11"));// Armistice
        $daysOff[] = date("Y-m-d", strtotime($year . "-12-25"));// Noel

        if (date('w', $timestamp) == 6 || date('w', $timestamp) == 0) {
            // Weekend
            return false;
        } elseif (in_array(date("Y-m-d", $timestamp), $daysOff)) {
            // Férié
            return false;
        } else {
            return true;
        }
    }
}
