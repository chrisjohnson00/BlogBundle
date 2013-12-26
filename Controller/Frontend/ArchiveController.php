<?php

/**
 * This file is part of the desarrolla2 project.
 *
 * Copyright (c)
 * Daniel González Cerviño <daniel.gonzalez@freelancemadrid.es>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Desarrolla2\Bundle\BlogBundle\Controller\Frontend;

use Desarrolla2\Bundle\BlogBundle\Model\PostStatus;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 *
 * Description of ArchiveController
 *
 * @Route("/archive")
 */
class ArchiveController extends Controller
{

    /**
     * @Route("/", name="_blog_archive")
     * @Method({"GET"})
     * @Template()
     */
    public function indexAction(Request $request)
    {
        return array(
            'items' => $this->getDoctrine()->getManager()
                    ->getRepository('BlogBundle:Post')->getArchiveItems(),
            'title' => $this->container->getParameter('blog.archive.title'),
            'description' => $this->container->getParameter('blog.archive.description'),
        );
    }

    /**
     * @Route("/{year}/{month}/{page}", name="_blog_archive_page", requirements={"year"="\d{4}", "month"="\d{1,2}", "page" = "\d{1,4}"}, defaults={"page" = "1" })
     * @Route("/{year}/{month}", requirements={"year"="\d{4}", "month"="\d{1,2}"})
     * @Method({"GET"})
     * @Template()
     */
    public function pageAction(Request $request)
    {
        $paginator = $this->get('knp_paginator');
        $year = $request->get('year');
        $month = $request->get('month');
        $page = $this->getPage();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('n', 'n', 'string');
        $count =
        $query = $this->getDoctrine()
            ->getManager()
            ->createNativeQuery(
                ' SELECT COUNT(*) as n FROM ( ' .
                ' SELECT p.id, ' .
                ' SUBSTRING(p.published_at, 1, 4) AS year, ' .
                ' SUBSTRING(p.published_at, 6, 2) AS month ' .
                ' FROM post AS p' .
                ' WHERE p.status = ' . PostStatus::PUBLISHED .
                ' HAVING year = :year ' .
                ' AND month = :month ' .
                ' ) AS items',
                $rsm
            )
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getSingleScalarResult();

        $query = $this->getDoctrine()
            ->getManager()
            ->createQuery(
                ' SELECT p as item, ' .
                ' SUBSTRING(p.publishedAt, 1, 4) as year, ' .
                ' SUBSTRING(p.publishedAt, 6, 2) as month ' .
                ' FROM BlogBundle:Post p ' .
                ' WHERE p.status = ' . PostStatus::PUBLISHED .
                ' HAVING year = :year ' .
                ' AND month = :month '
            )
            ->setHint('knp_paginator.count', $count)
            ->setParameter('year', $year)
            ->setParameter('month', $month);

        $pagination = $paginator->paginate(
            $query,
            $page,
            $this->container->getParameter('blog.items'),
            array('distinct' => false)
        );

        return array(
            'page' => $page,
            'year' => $year,
            'month' => $month,
            'pagination' => $pagination,
            'title' => $this->container->getParameter('blog.archive.title'),
            'description' => $this->container->getParameter('blog.archive.description'),
        );
    }

    protected function getPage()
    {
        $request = $this->getRequest();
        $page = (int)$request->get('page', 1);
        if ($page < 1) {
            $this->createNotFoundException('Page number is not valid' . $page);
        }

        return $page;
    }
}
