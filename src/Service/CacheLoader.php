<?php

namespace App\Service;

use League\Flysystem\FilesystemInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class CacheLoader
{
    protected JiraClient $jiraClient;
    private FilesystemInterface $jiraFilesystem;
    private DecoderInterface $decoder;

    public function __construct(JiraClient $jiraClient, FilesystemInterface $jiraFilesystem, DecoderInterface $decoder)
    {
        $this->jiraClient = $jiraClient;
        $this->jiraFilesystem = $jiraFilesystem;
        $this->decoder = $decoder;
    }

    public function getProjectList(): array
    {
        return $this->jiraFilesystem->has('projects.json')
            ? $this->decoder->decode($this->jiraFilesystem->read('projects.json'), JsonEncoder::FORMAT)
            : [];
    }

    public function getCurrentSprint(string $projectId): array
    {
        return $this->jiraFilesystem->has("$projectId/current_sprint.json")
            ? $this->decoder->decode($this->jiraFilesystem->read("$projectId/current_sprint.json"), JsonEncoder::FORMAT)
            : [];
    }

    public function getSprintList(string $projectId)
    {
        return $this->jiraFilesystem->has("$projectId/sprints.json")
            ? $this->decoder->decode($this->jiraFilesystem->read("$projectId/sprints.json"), JsonEncoder::FORMAT)
            : [];
    }

    public function getDoneStatuses()
    {
        return $this->jiraFilesystem->has("done_statuses.json")
            ? $this->decoder->decode($this->jiraFilesystem->read("done_statuses.json"), JsonEncoder::FORMAT)
            : [];
    }
}
