<?php

namespace App\Twig;

use App\Model\JiraCurrentSprint;
use App\Model\JiraProject;
use App\Model\JiraSprint;
use App\Service\CacheLoader;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BurndownExtension extends AbstractExtension
{
    protected RouterInterface $router;
    private CacheLoader $jiraCache;

    public function __construct(RouterInterface $router, CacheLoader $jiraCache)
    {
        $this->router = $router;
        $this->jiraCache = $jiraCache;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('burndown_sprint_url', [$this, 'burndownSprintUrl']),
            new TwigFunction('burndown_project_url', [$this, 'burndownProjectUrl']),
        ];
    }

    /**
     * @param JiraSprint|JiraCurrentSprint $sprint
     */
    public function burndownSprintUrl(JiraProject $project, $sprint): string
    {
        return $this->router->generate(
            'project_burndown_for_sprint',
            [
                'projectName' => urlencode($project->getName()),
                'sprintName'  => urlencode($sprint->getName()),
            ]
        );
    }

    /**
     * @param JiraProject                  $project
     * @param JiraSprint|JiraCurrentSprint $sprint
     *
     * @throws FileNotFoundException
     */
    public function burndownProjectUrl(JiraProject $project, $sprint = null): string
    {
        $currentSprint = $sprint ?: $this->jiraCache->getCurrentSprint($project->getId());

        return empty($currentSprint)
            ? '#'
            : $this->router->generate(
                'project_burndown_for_sprint',
                [
                    'projectName' => urlencode($project->getName()),
                    'sprintName'  => urlencode($currentSprint->getName()),
                ]
            );
    }
}
