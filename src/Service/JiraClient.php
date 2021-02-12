<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class JiraClient
{
    protected HttpClientInterface $client;

    public function __construct(HttpClientInterface $jiraClient)
    {
        $this->client = $jiraClient;
    }

    public function getProjectList(): array
    {
        $response = $this->client->request(
            'GET',
            '/rest/api/latest/project',
            [
                'query' => [
                    'jql' => "ORDER BY \"name\" DESC"
                ]
            ]
        );
        if (200 !== $response->getStatusCode()) {
            return [];
        }

        return $response->toArray();
    }

    public function getProject(string $projectId): array
    {
        return $this->client->request('GET', "/rest/api/latest/project/$projectId")->toArray(false);
    }

    public function getSprint(string $projectKey, string $sprintName): array
    {
        $response = $this->client->request(
            'GET',
            '/rest/greenhopper/latest/integration/teamcalendars/sprint/list',
            [
                'query' => [
                    'jql' => "project = $projectKey"
                ]
            ]
        );
        if (200 !== $response->getStatusCode()) {
            return [];
        }
        $projectSprints = $response->toArray()['sprints'];
        $foundSprint = array_filter($projectSprints, function (array $sprint) use ($sprintName) {
            return $sprintName === $sprint['name'];
        });

        return current($foundSprint) ?: [];
    }

    public function getSprintList(string $projectId): array
    {
        $response = $this->client->request('GET', "/rest/agile/1.0/board");
        if (200 !== $response->getStatusCode()) {
            return [];
        }

        $foundBoard = array_filter($response->toArray()['values'], function (array $board) use ($projectId) {
            return (int) $projectId === $board['location']['projectId'];
        });
        if (empty($foundBoard)) {
            return [];
        }

        $foundBoard = array_pop($foundBoard);
        $response = $this->client->request('GET', "/rest/agile/1.0/board/{$foundBoard['id']}/sprint");
        if (200 !== $response->getStatusCode()) {
            return [];
        }

        return $response->toArray()['values'];
    }

    public function getCurrentSprint(string $projectKey): array
    {
        $response =  $this->client->request(
            'GET',
            '/rest/greenhopper/latest/integration/teamcalendars/sprint/list',
            [
                'query' => [
                    'jql' => "project=$projectKey and Sprint not in closedSprints() and sprint not in futureSprints()"
                ]
            ]
        );
        if (200 !== $response->getStatusCode()) {
            return [];
        }

        return $response->toArray()['sprints'];
    }

    public function getInitialIssuesForSprint(string $projectId, string $sprintName, \DateTime $sprintStartDate): array
    {
        $response = $this->client->request(
            'POST',
            '/rest/api/latest/search',
            [
                'json' => [
                    'jql' => "sprint = \"$sprintName\" AND project = \"$projectId\" and created<\"{$sprintStartDate->format('Y-m-d H:i')}\" ORDER BY \"Story Points\" DESC, created DESC",
                    'maxResults' => 1000,
                ]
            ]
        );
        if (200 !== $response->getStatusCode()) {
            return  [];
        }

        return $response->toArray()['issues'];
    }

    public function getDailyDoneIssues(string $projectKey, string $sprintName, \DateTime $date, array $doneStatues): array
    {
        $doneStatuses = implode(',', $doneStatues);
        $response =  $this->client->request(
            'POST',
            '/rest/api/latest/search',
            [
                'json' => [
                    'jql' => "sprint = \"$sprintName\" AND project = \"$projectKey\" AND Status in ($doneStatuses) AND Status changed to ($doneStatuses) during(\"{$date->setTime(0, 0)->format('Y-m-d H:i')}\", \"{$date->setTime(23,59)->format('Y-m-d H:i')}\")  ORDER BY \"Story Points\" DESC, created DESC",
                    'maxResults' => 1000,
                ]
            ]
        );

        if (200 !== $response->getStatusCode()) {
            return [];
        }

        return $response->toArray()['issues'] ?: [];
    }

    public function getDoneStatuses(): array
    {
        $response = $this->client->request(
            'GET',
            '/rest/api/latest/status',
        );

        if (200 !== $response->getStatusCode()) {
            return [];
        }

        $doneStatuses = [];
        array_map(function (array $status) use (&$doneStatuses) {
            if ('done' === $status['statusCategory']['key']) {
                $doneStatuses[] = $status['id'];
            }
            return 'done' === $status['statusCategory']['key'];
        }, $response->toArray());

        return $doneStatuses;
    }
}
