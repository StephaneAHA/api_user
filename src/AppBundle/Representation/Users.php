<?php

namespace AppBundle\Representation;

use JMS\Serializer\Annotation\Type;

class Users extends ListeModeles
{
    /**
     * @Type("array<AppBundle\Entity\User>")
     */
    public $data;


}