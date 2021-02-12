<?php

namespace App\Controller;

use App\Service\BurndownHelper;
use App\Service\CacheLoader;
use App\Service\JiraClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class BurndownController extends AbstractController
{
    protected JiraClient $jiraClient;
    protected CacheLoader $jiraCache;
    protected RouterInterface $router;

    public function __construct(JiraClient $jiraClient, RouterInterface $router, CacheLoader $jiraCache)
    {
        $this->jiraClient = $jiraClient;
        $this->router = $router;
        $this->jiraCache = $jiraCache;
    }

    /**
     * @Route("/", name="default")
     */
    public function index(CacheLoader $jiraCache): Response
    {
        return $this->render('default/index.html.twig', [
            'projects' => $jiraCache->getProjectList(),
        ]);
    }

    /**
     * @Route("/{projectId}/projects-list", name="burndown_projects_list")
     */
    public function projectsList(Request $request, string $projectId): Response
    {
        $selectedProject = BurndownHelper::resolveProject($request, $this->jiraCache->getProjectList());

        return $this->render(
            'burndown/ajax/projects-list.html.twig',
            [
                'projects' => $this->jiraCache->getProjectList(),
                'project'  => $selectedProject,
            ]
        );
    }

    /**
     * @Route("/{projectId}/{sprintId}/sprints-list", name="burndown_sprints_list")
     */
    public function sprintstList(Request $request, string $projectId, string $sprintId): Response
    {
        $selectedProject = BurndownHelper::resolveProject($request, $this->jiraCache->getProjectList());
        $selectedSprint = BurndownHelper::resolveSprint($request, $this->jiraCache->getSprintList($selectedProject['id']));

        return $this->render(
            'burndown/ajax/sprints-list.html.twig',
            [
                'project'  => $selectedProject,
                'projects' => $this->jiraCache->getProjectList(),
                'sprint'   => $selectedSprint,
                'sprints'  => $this->jiraCache->getSprintList($selectedProject['id']),
            ]
        );
    }

    /**
     * @Route("/{projectId}/{sprintId}", name="burndown")
     */
    public function burndown(Request $request, string $projectId, string $sprintId): Response
    {
        $selectedProject = BurndownHelper::resolveProject($request, $this->jiraCache->getProjectList());
        $selectedSprint = BurndownHelper::resolveSprint($request, $this->jiraCache->getSprintList($selectedProject['id']));

        $startDate = \DateTime::createFromFormat('U', strtotime($selectedSprint['startDate']));
        $endDate = \DateTime::createFromFormat('U', strtotime($selectedSprint['endDate']));

        $burndown = BurndownHelper::computeBurndown(
            $selectedProject,
            $selectedSprint,
            $startDate,
            $endDate,
            $this->jiraClient->getInitialIssuesForSprint($projectId, $selectedSprint['name'], $startDate),
            $this->jiraCache->getDoneStatuses(),
            $this->jiraClient
        );

        return $this->render(
            'burndown/index.html.twig',
            [
                'projectsListUrl' => $this->router->generate('burndown_projects_list', ['projectId' => $projectId]),
                'sprintstListUrl' => $this->router->generate('burndown_sprints_list', ['projectId' => $projectId, 'sprintId' => $sprintId]),
                'project'         => $selectedProject,
                'sprint'          => $selectedSprint,
                'burndown'        => $burndown,
            ]
        );
    }
}
