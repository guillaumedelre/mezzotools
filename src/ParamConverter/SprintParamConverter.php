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
            $this->cacheLoader->getSprintList($request->attributes->get('resolvedProject')['id']),
            function (array $sprint) use ($request, $configuration) {
                return mb_strtolower($sprint['name']) === mb_strtolower(
                        $request->attributes->get($configuration->getName())
                    );
            }
        );

        if (false === $resolvedSprint = current(array_values($found))) {
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
