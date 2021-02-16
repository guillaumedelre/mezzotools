<?php

namespace App\ParamConverter;

use App\Model\JiraSprint;
use App\Service\CacheLoader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SprintParamConverter implements ParamConverterInterface
{
    protected CacheLoader $cacheLoader;
    protected RequestStack $requestStack;

    public function __construct(CacheLoader $cacheLoader, RequestStack $requestStack)
    {
        $this->cacheLoader = $cacheLoader;
        $this->requestStack = $requestStack;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        if (empty($request->attributes->get('resolvedProject'))) {
            throw new NotFoundHttpException();
        }

        $found = array_filter(
            $this->cacheLoader->getSprintList($request->attributes->get('resolvedProject')->getId()),
            function (JiraSprint $sprint) use ($request, $configuration) {
                return mb_strtolower($sprint->getName()) === mb_strtolower(urldecode($request->attributes->get($configuration->getName())));
            }
        );

        $resolvedSprint = current($found);
        if (empty($resolvedSprint) || empty($resolvedSprint->getName())) {
            return false;
        }

        $request->attributes->set('resolvedSprint', $resolvedSprint);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return JiraSprint::class === $configuration->getClass()
            && 'sprintName' === $configuration->getName()
            && !empty($this->requestStack->getCurrentRequest()->attributes->get('resolvedProject'));
    }

}
