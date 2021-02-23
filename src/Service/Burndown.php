<?php

namespace App\Service;

use App\Model\JiraCurrentSprint;
use App\Model\JiraProject;
use App\Model\JiraSprint;

class Burndown
{
    protected JiraClient $jiraClient;
    protected int $initialPoints = 0;
    private \DateTime $startDate;
    private \DateTime $endDate;
    private CacheLoader $jiraCache;
    private array $doneStatuses;

    public function __construct(JiraClient $jiraClient, CacheLoader $jiraCache)
    {
        $this->jiraClient = $jiraClient;
        $this->jiraCache = $jiraCache;
        $this->doneStatuses = $this->jiraCache->getDoneStatuses();
    }

    /**
     * @param JiraProject $resolvedProject
     * @param JiraCurrentSprint|JiraSprint $resolvedSprint
     */
    public function compute(JiraProject $resolvedProject, $resolvedSprint)
    {
        $this->startDate = ($resolvedSprint instanceof JiraCurrentSprint)
            ? $resolvedSprint->getStart()
            : $resolvedSprint->getStartDate();
        $this->endDate = ($resolvedSprint instanceof JiraCurrentSprint)
            ? $resolvedSprint->getEnd()
            : $resolvedSprint->getEndDate();
        $now = (new \DateTime())->setTimezone(new \DateTimeZone('+00:00'))->setTime(23, 59, 59);

        $this->initialPoints = 0;
        array_map(function (array $issue) use (&$initialPoints) {
            $this->initialPoints += $issue['fields']['customfield_10028'] ?: 0;
        }, $this->jiraClient->getInitialIssuesForSprint($resolvedProject->getId(), $resolvedSprint->getName(), $this->startDate));

        $idealPoints = [
            'type' => 'line',
            'label' => 'Ideal burndown',
            'yAxisID' => 'normal_axis',
            'fill' => false,
            'backgroundColor' => 'rgba(192,85,75,0.5)',
            'borderColor' => 'rgb(192,85,75)',
            'borderWidth' => 2,
            'pointRadius' => 0,
            'data' => [],
        ];
        $zeroPoints = [
            'type' => 'line',
            'label' => 'zero',
            'yAxisID' => 'normal_axis',
            'fill' => false,
            'legend' => [
                'display' => false,
                'enabled' => false,
            ],
            'borderColor' => 'rgb(255,255,255)',
            'borderWidth' => 1,
            'pointRadius' => 0,
            'data' => [],
        ];
        $realPoints = [
            'type' => 'line',
            'label' => 'Burndown',
            'yAxisID' => 'normal_axis',
            'fill' => false,
            'backgroundColor' => 'rgba(27,156,215,0.5)',
            'borderColor' => 'rgb(27,156,215)',
            'borderWidth' => 3,
            'lineTension' => .1,
            'data' => [],
        ];
        $dailyPoints = [
            'type' => 'bar',
            'label' => 'Done',
            'yAxisID' => 'normal_axis',
            'backgroundColor' => 'rgba(111,171,53,0.5)',
            'borderColor' => 'rgb(111,171,53)',
            'borderWidth' => 2,
            'data' => [],
        ];

        $diffDays = self::countWorkedDaysBetween($this->startDate, $this->endDate);

        $chartLabels = [];
        $increment = 0;
        $rincrement = $diffDays;
        for ($dayOfSprint = clone $this->startDate; $dayOfSprint <= clone $this->endDate->setTime(23, 59, 59); $dayOfSprint->modify('+1 day')) {
            if (!self::isWorked($dayOfSprint)) {
                continue;
            }
            $chartLabels[] = $dayOfSprint->format('Y-m-d');

            $pointsForTheDay = 0;
            array_map(function (array $issue) use (&$pointsForTheDay, $dayOfSprint) {
                $pointsForTheDay += $issue['fields']['customfield_10028'] ?: 0;
            }, $this->jiraClient->getDailyDoneIssues($resolvedProject->getId(), $resolvedSprint->getName(), $dayOfSprint, $this->doneStatuses));

            $dailyPoints['data'][] = ['x' => $dayOfSprint->format('Y-m-d'), 'y' => $pointsForTheDay];

            $idealPoints['data'][] = [
                'x' => $dayOfSprint->format('Y-m-d'),
                'y' => $rincrement * $this->initialPoints / $diffDays,
            ];

            $zeroPoints['data'][] = ['x' => $dayOfSprint->format('Y-m-d'), 'y' => 0];

            $realPoints['data'][] = ($dayOfSprint <= $now)
                ? [
                    'x' => $dayOfSprint->format('Y-m-d'),
                    'y' => (0 === $increment ? $this->initialPoints : $realPoints['data'][$increment - 1]['y']) - $dailyPoints['data'][$increment]['y'],
                ] : [
                    'x' => $dayOfSprint->format('Y-m-d'),
                    'y' => null,
                ];

            $increment++;
            $rincrement--;
        }

        $zeroPoints['data'][] = ['x' => $dayOfSprint->format('Y-m-d'), 'y' => 0];
        $chartLabels[] = $dayOfSprint->format('Y-m-d');

        return [
            'labels' => $chartLabels,
            'datasets' => [
                'ideal' => $idealPoints,
                'daily' => $dailyPoints,
                'real' => $realPoints,
                'zero' => $zeroPoints,
            ],
        ];
    }

    private static function countWorkedDaysBetween(\DateTime $startDate, \DateTime $endDate): int
    {
        $nbDays = 0;
        for ($dayOfSprint = clone $startDate; $dayOfSprint <= clone $endDate; $dayOfSprint->modify('+1 day')) {
            if (!self::isWorked($dayOfSprint)) {
                continue;
            }
            $nbDays++;
        }

        return $nbDays;
    }

    private static function isWorked(\DateTime $dayOfSprint): bool
    {
        $easterDate = \DateTime::createFromFormat('U', easter_date($dayOfSprint->format('Y')), new \DateTimeZone('+00:00'));
        $daysOff = [];
        $daysOff[] = \DateTime::createFromFormat('U', strtotime("+1 day", $easterDate->getTimestamp()))->format('Y-m-d');// Paques
        $daysOff[] = \DateTime::createFromFormat('U', strtotime("+39 day", $easterDate->getTimestamp()))->format('Y-m-d'); // Ascenssion
        $daysOff[] = \DateTime::createFromFormat('U', strtotime("+50 day", $easterDate->getTimestamp()))->format('Y-m-d');// Pentecotes
        $daysOff[] = $dayOfSprint->format('Y') . "-01-01";// 1er janvier
        $daysOff[] = $dayOfSprint->format('Y') . "-05-01";// Fete du travail
        $daysOff[] = $dayOfSprint->format('Y') . "-05-08";// Victoire des allies
        $daysOff[] = $dayOfSprint->format('Y') . "-07-14";// Fete nationale
        $daysOff[] = $dayOfSprint->format('Y') . "-08-15";// Assomption
        $daysOff[] = $dayOfSprint->format('Y') . "-11-01";// Toussaint
        $daysOff[] = $dayOfSprint->format('Y') . "-11-11";// Armistice
        $daysOff[] = $dayOfSprint->format('Y') . "-12-25";// Noel
        if (
            ($dayOfSprint->format('w') == 6 || $dayOfSprint->format('w') == 0) // Weekend
            || in_array($dayOfSprint->format('Y-m-d'), $daysOff) // férié
        ) {
            return false;
        }

        return true;
    }
}
