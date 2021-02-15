<?php

namespace App\ParamConverter;

use App\Model\JiraProject;
use App\Service\CacheLoader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectParamConverter implements ParamConverterInterface
{
    protected CacheLoader $cacheLoader;

    public function __construct(CacheLoader $cacheLoader)
    {
        $this->cacheLoader = $cacheLoader;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $found = array_filter($this->cacheLoader->getProjectList(), function (array $project) use ($request, $configuration) {
            return mb_strtolower($project['name']) === mb_strtolower($request->attributes->get($configuration->getName()));
        });

        if (false === $resolvedProject = current(array_values($found))) {
            return false;
        }

        $request->attributes->set('resolvedProject', $resolvedProject);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return
            JiraProject::class === $configuration->getClass()
            && 'projectName' === $configuration->getName();
    }

}
