<?php

/**
 * Created by IntelliJ IDEA.
 * User: Ivan Kalita
 * Date: 22.09.16
 * Time: 15:56.
 */
namespace AppBundle\Manager;

use AppBundle\Entity\TemporaryEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\NonUniqueResultException;

class UserManager extends BaseEntityManager
{
    /**
     * @param $token
     *
     * @return User|null
     */
    public function findOneByActiveAccessToken($token)
    {
        if (!$token) {
            return null;
        }
        $qb = $this->getRepository()->createQueryBuilder('u')
            ->addSelect('at')
            ->join('u.accessTokens', 'at')
            ->where('at.token like :token')
            ->andWhere(TemporaryEntity::getIsActiveCondition('at'))
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime(null, new \DateTimeZone('UTC')));

        try {
            $user = $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $exception) {
            $user = null;
        }

        return $user;
    }
}