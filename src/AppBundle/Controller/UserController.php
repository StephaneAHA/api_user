<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Exception\ResourceValidationException;
use AppBundle\Representation\Users;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\Validator\ConstraintViolationList;
use Nelmio\ApiDocBundle\Annotation as Doc;
use AppBundle\Form\UserType;


class UserController extends FOSRestController
{


    /**
     * @Rest\Get("/users", name="app_users_list")
     * @Rest\QueryParam(
     *     name="keyword",
     *     requirements="[a-zA-Z0-9]",
     *     nullable=true,
     *     description="Le mot clé pour le recherche d'un utilisateur."
     * )
     * @Rest\QueryParam(
     *     name="order",
     *     requirements="asc|desc",
     *     default="asc",
     *     description="l'ordre de tri de la liste"
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     default="50000",
     *     description="Le nombre maximum  d'utilisateur par page"
     * )
     * @Rest\QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     default="0",
     *     description="Le numero par lequel commencer la pagination "
     * )
     *
     * @Doc\ApiDoc(
     *     resource=true,
     *     description="Récupérer la liste des utilisateurs suivant des paramètres ."
     * )
     * @Rest\View()
     */

    public function listAction(ParamFetcherInterface $paramFetcher)
    {
        $pager = $this->getDoctrine()->getRepository(User::class)->search(
            $paramFetcher->get('keyword'),
            $paramFetcher->get('order'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('offset')
        );

        return new Users($pager);

    }


    /**
     * @Rest\Get(
     *     path = "/users/{id}",
     *     name = "app_user_show",
     *     requirements = {"id"="\d+"}
     * )
     *
     * @Doc\ApiDoc(
     *     resource=true,
     *     description="Afficher un utilisateur à partir de son ID."
     * )
     * @Rest\View
     */
    public function showAction(User $user)
    {
        return $user;
    }



    /**
     * @Rest\View(StatusCode = 205)
     * @Rest\Delete(
     *     path = "/users/{id}",
     *     name = "app_user_delete",
     *     requirements = {"id"="\d+"}
     * )
     * @Doc\ApiDoc(
     *     resource=true,
     *     description="Supprimer un utilisateur.",
     *
     * )
     */
    public function deleteAction(User $user)
    {

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        return;
    }



    /**
     * @Rest\View(StatusCode = 202)
     * @Rest\Put(
     *     path = "/users/{id}",
     *     name = "app_user_update",
     *     requirements = {"id"="\d+"}
     * )
     *
     * @ParamConverter("newUser", converter="fos_rest.request_body")
     *
     * @Doc\ApiDoc(
     *     resource=true,
     *     description="Mettre à jour un utilisateur.",
     *     input={"class"=UserType::class, "name"=""}
     * )
     */
    public function updateAction(User $user, User $newUser, ConstraintViolationList $violations)
    {
        if (count($violations)) {
            $message = 'la requete envoyé contient des données invalides. Voici les erreurs que vous devez corriger: ';
            foreach ($violations as $violation) {
                $message .= sprintf("Field %s: %s ", $violation->getPropertyPath(), $violation->getMessage());
            }

            throw new ResourceValidationException($message);
        }

        $datemodifie = new \DateTime();
//
//
        $loLogger = $this->get('logger');

        $loLogger->info('L\'utilisateur '.$user->getId().' dont le nom et prénoms sont '.$user->getLastname().' '
            .$user->getFirstname().' a été changé en '. $newUser->getLastname() .' '.$newUser->getFirstname() .' à '.date_format($datemodifie,"d/m/Y H:i:s"));

        $user->setFirstname($newUser->getFirstname());
        $user->setLastname($newUser->getLastname());
        $user->setUpdatedate($datemodifie);

        $this->getDoctrine()->getManager()->flush();

        $statutaction = [];
        if (!is_null($user)) {
            $liste['codestatut'] = '202';
            $liste['message'] = 'Utilisateur modifier avec succès';
            $liste['user'] = $user;
            array_push($statutaction, $liste);
        }

        return $statutaction;
    }



    /**
     * @Rest\Post(
     * path = "/users",
     * name = "app_user_create"
     * )
     * @Rest\View(StatusCode = 201)
     * @ParamConverter("user", converter="fos_rest.request_body")
     * @Doc\ApiDoc(
     *     resource=true,
     *     description="Ajouter un nouveau utilisateur.",
     *     input={"class"=UserType::class, "name"=""}
     * )
     */
    public function createAction(User $user, ConstraintViolationList $violations)
    {

        if (count($violations) > 0) {
            $message = 'la requete envoyé contient des données invalides. Voici les erreurs que vous devez corriger: ';
            foreach ($violations as $violation) {
                $message .= sprintf("Field %s: %s ", $violation->getPropertyPath(), $violation->getMessage());
            }

            throw new ResourceValidationException($message);
        }

        $statutaction = [];
        $em = $this->getDoctrine()->getManager();
        $user->setCreationdate(new \DateTime());
        $user->setUpdatedate(new \DateTime());
        $em->persist($user);
        $em->flush();

        if (!is_null($user)) {
            $liste['codestatut'] = '201';
            $liste['message'] = 'Utilisateur enregistrer avec succès';
            $liste['user'] = $user;
            array_push($statutaction, $liste);
        }

        return $statutaction;
    }


}