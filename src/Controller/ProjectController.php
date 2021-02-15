<?php

namespace App\Controller;

use App\Service\BurndownHelper;
use App\Service\CacheLoader;
use App\Service\JiraClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use App\Model\JiraSprint;
use App\Model\JiraProject;

/**
 * @Route("/", name="project_")
 */
class ProjectController extends AbstractController
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
     * @Route("/{projectName}", name="burndown_current_sprint")
     * @ParamConverter("projectName", class=JiraProject::class, isOptional=false)
     */
    public function burndownForCurrentSprint(array $resolvedProject): Response
    {
        if (empty($resolvedSprint)) {
            $resolvedSprint = $this->jiraCache->getCurrentSprint($resolvedProject['id']);
        }

        return $this->redirectToRoute(
            'project_burndown_for_sprint',
            [
                'projectName' => $resolvedProject['name'], 'sprintName' => $resolvedSprint['name']
            ]
        );
    }

    /**
     * @Route("/{projectName}/{sprintName}", name="burndown_for_sprint")
     * @ParamConverter("projectName", class=JiraProject::class, isOptional=false)
     * @ParamConverter("sprintName", class=JiraSprint::class, isOptional=false)
     */
    public function burndownForSprint(array $resolvedProject, array $resolvedSprint = []): Response
    {
        if (empty($resolvedSprint)) {
            $resolvedSprint = $this->jiraCache->getCurrentSprint($resolvedProject['id']);
        }

        $startDate = !empty($resolvedSprint['startDate'])
            ? \DateTime::createFromFormat('U', strtotime($resolvedSprint['startDate']))
            : \DateTime::createFromFormat('dmYHis', $resolvedSprint['start']);
        $endDate = !empty($resolvedSprint['endDate'])
            ? \DateTime::createFromFormat('U', strtotime($resolvedSprint['startDate']))
            : \DateTime::createFromFormat('dmYHis', $resolvedSprint['end']);
        $burndown = BurndownHelper::computeBurndown(
            $resolvedProject,
            $resolvedSprint,
            $startDate,
            $endDate,
            $this->jiraClient->getInitialIssuesForSprint($resolvedProject['id'], $resolvedSprint['name'], $startDate),
            $this->jiraCache->getDoneStatuses(),
            $this->jiraClient
        );

        return $this->render(
            'burndown/index.html.twig',
            [
                'projectsListUrl' => $this->router->generate(
                    'ajax_projects_list',
                    [
                        'projectId' => $resolvedProject['id'],
                    ]
                ),
                'sprintstListUrl' => $this->router->generate(
                    'ajax_sprints_list',
                    [
                        'projectId' => $resolvedProject['id'],
                        'sprintId' => $resolvedSprint['id'],
                    ]
                ),
                'project'         => $resolvedProject,
                'sprint'          => $resolvedSprint,
                'burndown'        => $burndown,
            ]
        );
    }

    /**
     * @Route("/", name="index")
     */
    public function index(CacheLoader $jiraCache): Response
    {
        return $this->render(
            'default/index.html.twig',
            [
                'projects' => $jiraCache->getProjectList(),
            ]
        );
    }

}
