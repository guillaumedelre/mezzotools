<?php

namespace App\Controller;

use App\Service\CacheLoader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\JiraProject;
use App\Model\JiraSprint;

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
     * @Route("/{projectName}/projects", name="projects_list")
     * @ParamConverter("projectName", class=JiraProject::class, isOptional=false)
     */
    public function projectsList(JiraProject $resolvedProject): Response
    {
        return $this->render(
            'ajax/projects-list.html.twig',
            [
                'projects' => $this->jiraCache->getProjectList(),
                'project'  => $resolvedProject,
            ]
        );
    }

    /**
     * @Route("/{projectName}/{sprintName}/sprints", name="sprints_list")
     * @ParamConverter("projectName", class=JiraProject::class, isOptional=false)
     * @ParamConverter("sprintName", class=JiraSprint::class, isOptional=false)
     */
    public function sprintstList(JiraProject $resolvedProject, JiraSprint $resolvedSprint): Response
    {
        return $this->render(
            'ajax/sprints-list.html.twig',
            [
                'project'  => $resolvedProject,
                'projects' => $this->jiraCache->getProjectList(),
                'sprint'   => $resolvedSprint,
                'sprints'  => $this->jiraCache->getSprintList($resolvedProject->getId()),
            ]
        );
    }
}
