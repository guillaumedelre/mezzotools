<?php

namespace App\Service;

use App\Model\JiraCurrentSprint;
use App\Model\JiraProject;
use App\Model\JiraSprint;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class CacheLoader implements SerializerAwareInterface
{
    use SerializerAwareTrait;

    protected JiraClient $jiraClient;
    private FilesystemInterface $jiraFilesystem;
    private DecoderInterface $decoder;

    public function __construct(JiraClient $jiraClient, FilesystemInterface $jiraFilesystem, DecoderInterface $decoder)
    {
        $this->jiraClient = $jiraClient;
        $this->jiraFilesystem = $jiraFilesystem;
        $this->decoder = $decoder;
    }

    /**
     * @return JiraProject[]
     * @throws FileNotFoundException
     */
    public function getProjectList(): array
    {
        return $this->jiraFilesystem->has('projects.json')
            ? $this->serializer->deserialize($this->jiraFilesystem->read('projects.json'), JiraProject::class . '[]', JsonEncoder::FORMAT)
            : [];
    }

    /**
     * @return ?JiraCurrentSprint
     * @throws FileNotFoundException
     */
    public function getCurrentSprint(string $projectId): ?JiraCurrentSprint
    {
        return $this->jiraFilesystem->has("$projectId/current_sprint.json")
            ? $this->serializer->deserialize($this->jiraFilesystem->read("$projectId/current_sprint.json"), JiraCurrentSprint::class, JsonEncoder::FORMAT, [DateTimeNormalizer::FORMAT_KEY => 'dmYHis'])
            : null;
    }

    /**
     * @return JiraSprint[]
     * @throws FileNotFoundException
     */
    public function getSprintList(string $projectId): array
    {
        return $this->jiraFilesystem->has("$projectId/sprints.json")
            ? $this->serializer->deserialize($this->jiraFilesystem->read("$projectId/sprints.json"), JiraSprint::class . '[]', JsonEncoder::FORMAT)
            : [];
    }

    /**
     * @throws FileNotFoundException
     */
    public function getDoneStatuses(): array
    {
        return $this->jiraFilesystem->has("done_statuses.json")
            ? $this->decoder->decode($this->jiraFilesystem->read("done_statuses.json"), JsonEncoder::FORMAT)
            : [];
    }
}
