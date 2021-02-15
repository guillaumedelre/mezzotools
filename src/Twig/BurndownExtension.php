<?php

namespace App\Twig;

use App\Service\CacheLoader;
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

    public function burndownSprintUrl(array $project, array $sprint): string
    {
        return $this->router->generate('project_burndown_for_sprint', ['projectName' => urlencode($project['name']), 'sprintName' => urlencode($sprint['name'])]);
    }

    public function burndownProjectUrl(array $project, ?array $sprint = null): string
    {
        $currentSprint = $sprint ?: $this->jiraCache->getCurrentSprint($project['id']);
        return empty($currentSprint)
            ? '#'
            : $this->router->generate('project_burndown_for_sprint', ['projectName' => urlencode($project['name']), 'sprintName' => urlencode($currentSprint['name'])]);
    }
}
