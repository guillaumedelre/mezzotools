<?php

namespace App\Controller;

use App\Service\BurndownHelper;
use App\Service\CacheLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/ajax", name="ajax_")
 */
class AjaxController extends AbstractController
{
    protected CacheLoader $jiraCache;

    public function __construct(CacheLoader $jiraCache)
    {
        $this->jiraCache = $jiraCache;
    }

    /**
     * @Route("/{projectId}/projects", name="projects_list")
     */
    public function projectsList(string $projectId): Response
    {
        $selectedProject = BurndownHelper::resolveProject($projectId, $this->jiraCache->getProjectList());

        return $this->render(
            'ajax/projects-list.html.twig',
            [
                'projects' => $this->jiraCache->getProjectList(),
                'project'  => $selectedProject,
            ]
        );
    }

    /**
     * @Route("/{projectId}/{sprintId}/sprints-list", name="sprints_list")
     */
    public function sprintstList(string $projectId, string $sprintId): Response
    {
        $selectedProject = BurndownHelper::resolveProject($projectId, $this->jiraCache->getProjectList());
        $selectedSprint = BurndownHelper::resolveSprint(
            (int)$sprintId, $this->jiraCache->getSprintList($selectedProject['id'])
        );

        return $this->render(
            'ajax/sprints-list.html.twig',
            [
                'project'  => $selectedProject,
                'projects' => $this->jiraCache->getProjectList(),
                'sprint'   => $selectedSprint,
                'sprints'  => $this->jiraCache->getSprintList($selectedProject['id']),
            ]
        );
    }
}
