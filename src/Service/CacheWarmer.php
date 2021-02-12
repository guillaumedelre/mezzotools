<?php

namespace App\Service;

use League\Flysystem\FilesystemInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class CacheWarmer implements SerializerAwareInterface
{
    use SerializerAwareTrait;

    protected JiraClient $jiraClient;
    private FilesystemInterface $jiraFilesystem;

    public function __construct(JiraClient $jiraClient, FilesystemInterface $jiraFilesystem)
    {
        $this->jiraClient = $jiraClient;
        $this->jiraFilesystem = $jiraFilesystem;
    }

    public function refresh(): bool
    {
        $projects = $this->jiraClient->getProjectList();
        $this->refreshCache("projects.json", $projects);

        $doneStatuses = $this->jiraClient->getDoneStatuses();
        $this->refreshCache("done_statuses.json", $doneStatuses);

        array_map(function (array $project) {

            $this->refreshCache("$project[id]/project.json", $project);

            $this->refreshCache("$project[id]/sprints.json", $this->jiraClient->getSprintList($project['id']));

            array_map(function (array $sprint) use ($project) {
                $this->refreshCache("$project[id]/current_sprint.json", $sprint);
            }, $this->jiraClient->getCurrentSprint($project['key']));

        }, $projects);

        if (!empty($projects)) {
            return true;
        }

        return false;
    }

    private function refreshCache($key, $data)
    {
        $content = $this->serializer->serialize($data, JsonEncoder::FORMAT);
        if ($this->jiraFilesystem->has($key)) {
            $this->jiraFilesystem->update($key, $content);
        } else {
            $this->jiraFilesystem->write($key, $content);
        }
    }
}
