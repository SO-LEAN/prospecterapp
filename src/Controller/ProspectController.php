<?php

namespace App\Controller;

use App\Traits\ControllerTrait;
use App\Form\CreateOrganizationForm;
use App\Presenter\CreateOrganizationPresenter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Solean\CleanProspecter\Exception\UseCase\UseCaseException;
use Solean\CleanProspecter\UseCase\CreateOrganization\CreateOrganizationRequest;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation;

/**
 * Class ProspectController.
 *
 * @Security("has_role('ROLE_PROSPECTOR')")
 * @Route(service="App\Controller\ProspectController")
 */
class ProspectController
{
    use ControllerTrait;

    /**
     * @Route("/", name="index")
     * @Route("/dashboard/view", name="dashboard_display")
     */
    public function displayDashboard()
    {
        return $this->render('page/dashboard.html.twig');
    }

    /**
     * @Route("/prospect/add", name="prospect_create")
     */
    public function addProspect()
    {
        return $this->render('page/prospect-add.html.twig');
    }

    /**
     * @param HttpFoundation\Request $request
     *
     * @return HttpFoundation\RedirectResponse|HttpFoundation\Response
     *
     * @Route("organization/add", name="organization_create")
     */
    public function createOrganization(HttpFoundation\Request $request)
    {
        $form = $this->createForm(CreateOrganizationForm::class);

        $form->handleRequest($request);
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $response = $this->getUseCases()->createOrganization(
                    new CreateOrganizationRequest($data['email'], 'FR', $data['corporateName'], 'SARL', null),
                    new CreateOrganizationPresenter()
                );

                return $this->redirectToRoute('organization_view', [
                    'id' => $response->getId(),
                ]);
            }
        } catch (UseCaseException $e) {
            foreach ($e->getRequestErrors() as $key => $msg) {
                $form->get($key)->addError(new FormError($msg));
            }
        }

        return $this->render('page/organization-add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/organization/view/{id}", name="organization_view")
     */
    public function viewOrganization($id)
    {
        return $this->render('page/prospect-add.html.twig');
    }
}
