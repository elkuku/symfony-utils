<?php

namespace Elkuku\SymfonyUtils\Type;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method ExpectedUserType|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpectedUserType|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpectedUserType[]    findAll()
 * @method ExpectedUserType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<ExpectedUserRepository>
 */
class ExpectedUserRepository extends ServiceEntityRepository
{
}
