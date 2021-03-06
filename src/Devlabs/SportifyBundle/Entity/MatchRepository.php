<?php

namespace Devlabs\SportifyBundle\Entity;

/**
 * Class MatchRepository
 * @package Devlabs\SportifyBundle\Entity
 */
class MatchRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Get a list of matches which have not been scored/finished yet
     *
     * @param User $user
     * @param $tournamentId
     * @param $dateFrom
     * @param $dateTo
     * @return array
     */
    public function getNotScored(User $user, $tournamentId, $dateFrom, $dateTo)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->join('m.homeTeamId', 'tm')
            ->join('m.tournamentId', 't')
            ->join('t.scores', 's', 'WITH', 's.userId = :user_id')
            ->leftJoin('m.predictions', 'p', 'WITH', 'p.userId = :user_id')
            ->where('p.scoreAdded IS NULL OR p.scoreAdded = 0')
            ->andWhere('m.homeGoals IS NULL OR m.awayGoals IS NULL')
            ->andWhere('m.datetime >= :date_from AND m.datetime <= :date_to')
            ->orderBy('m.datetime')
            ->addOrderBy('m.tournamentId')
            ->addOrderBy('tm.name')
            ->setParameters(array(
                'user_id' => $user->getId(),
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ));

        // prepare a different query, if a tournament is selected for filtering
        if ($tournamentId !== 'all') {
            $query->andWhere('m.tournamentId = :tournament_id')
                ->setParameter('tournament_id', $tournamentId);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Get a list of matches which have already been scored/finished
     *
     * @param User $user
     * @param $tournamentId
     * @param $dateFrom
     * @param $dateTo
     * @return array
     */
    public function getAlreadyScored(User $user, $tournamentId, $dateFrom, $dateTo)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->join('m.homeTeamId', 'tm')
            ->join('m.tournamentId', 't')
            ->join('t.scores', 's', 'WITH', 's.userId = :user_id')
            ->leftJoin('m.predictions', 'p', 'WITH', 'p.userId = :user_id')
            ->where('p.scoreAdded = 1 OR p.id IS NULL')
            ->andWhere('m.homeGoals IS NOT NULL AND m.awayGoals IS NOT NULL')
            ->andWhere('m.datetime >= :date_from AND m.datetime <= :date_to')
            ->orderBy('m.datetime', 'DESC')
            ->addOrderBy('m.tournamentId')
            ->addOrderBy('tm.name')
            ->setParameters(array(
                'user_id' => $user->getId(),
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ));

        // prepare a different query, if a tournament is selected for filtering
        if ($tournamentId !== 'all') {
            $query->andWhere('m.tournamentId = :tournament_id')
                ->setParameter('tournament_id', $tournamentId);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Get a list of matches which have final score
     * but there are NOT SCORED predictions for these matches
     *
     * @return array
     */
    public function getFinishedNotScored()
    {
        $queryResult = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->join('m.predictions', 'p')
            ->where('p.scoreAdded IS NULL OR p.scoreAdded = 0')
            ->andWhere('m.homeGoals IS NOT NULL AND m.awayGoals IS NOT NULL')
            ->orderBy('m.id')
            ->getQuery()
            ->getResult();

        $result = array();

        /**
         * Iterate the query result array
         * and set the item key to be the match id
         */
        foreach ($queryResult as $match) {
            $result[$match->getId()] = $match;
        }

        return $result;
    }

    /**
     * Get a list of matches which have final score
     *
     * @param Tournament $tournament
     * @return array
     */
    public function getFinishedByTournament(Tournament $tournament)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->where('m.homeGoals IS NOT NULL AND m.awayGoals IS NOT NULL')
            ->andWhere('m.tournamentId = :tournament_id')
            ->setParameter('tournament_id', $tournament->getId())
            ->orderBy('m.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Method for getting a list of upcoming matches
     *
     * @param $dateFrom
     * @param $dateTo
     * @return array
     */
    public function getUpcoming($dateFrom, $dateTo)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->join('m.homeTeamId', 'tm')
            ->where('m.notificationSent = 0')
            ->andWhere('m.datetime >= :date_from AND m.datetime <= :date_to')
            ->orderBy('m.datetime')
            ->addOrderBy('tm.name')
            ->setParameters(array(
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ))
            ->getQuery()
            ->getResult();
    }

    /**
     * Get a list all matches for a tournament
     *
     * @param Tournament $tournament
     * @return array
     */
    public function getAllByTournament(Tournament $tournament)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->join('m.homeTeamId', 'tm')
            ->where('m.tournamentId = :tournament_id')
            ->setParameter('tournament_id', $tournament->getId())
            ->orderBy('m.datetime')
            ->addOrderBy('tm.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all matches for a given tournament and time range
     *
     * @param Tournament $tournament
     * @param $dateFrom
     * @param $dateTo
     * @return array
     */
    public function getAllByTournamentAndTimeRange(Tournament $tournament, $dateFrom, $dateTo)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->join('m.homeTeamId', 'tm')
            ->where('m.tournamentId = :tournament_id')
            ->andWhere('m.datetime >= :date_from AND m.datetime <= :date_to')
            ->setParameters(array(
                'tournament_id' => $tournament->getId(),
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ))
            ->orderBy('m.datetime')
            ->addOrderBy('tm.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get matches by complex filter criteria -
     * a custom query based on various parameters passed
     *
     * @param $params
     * @return array
     */
    public function findFiltered($params)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from('DevlabsSportifyBundle:Match', 'm')
            ->join('m.homeTeamId', 'h_tm')
            ->join('m.awayTeamId', 'a_tm')
            ->join('m.tournamentId', 't')
            ->orderBy('m.datetime')
            ->addOrderBy('m.tournamentId')
            ->addOrderBy('h_tm.name');

        if (key_exists('date_from', $params)) {
            $query->andWhere('m.datetime >= :date_from')
                ->setParameter('date_from', $params['date_from']);
        }

        if (key_exists('date_to', $params)) {
            $query->andWhere('m.datetime <= :date_to')
                ->setParameter('date_to', $params['date_to']);
        }

        if (key_exists('tournament', $params)) {
            $query->andWhere('m.tournamentId = :tournament_id')
                ->setParameter('tournament_id', $params['tournament']);
        }

        if (key_exists('team', $params)) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('h_tm.name', ':team_string'),
                $query->expr()->like('a_tm.name', ':team_string')
            ))
                ->setParameter('team_string', '%' . $params['team'] . '%');
        }

        return $query->getQuery()->getResult();
    }
}
