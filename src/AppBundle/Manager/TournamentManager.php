<?php

namespace AppBundle\Manager;

use AppBundle\Entity\MetricValue;
use AppBundle\Entity\Tournament;
use AppBundle\Entity\TournamentMetricCondition;
use AppBundle\Entity\TournamentTeam;
use AppBundle\Entity\TournamentTeamParticipant;
use AppBundle\Entity\User;

class TournamentManager extends BaseEntityManager
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var MetricManager
     */
    private $metricManager;

    /**
     * @param UserManager $userManager
     */
    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param MetricManager $metricManager
     */
    public function setMetricManager(MetricManager $metricManager)
    {
        $this->metricManager = $metricManager;
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $period
     */
    public function add($name, $type, $period)
    {
        $startDt = new \DateTime('now', new \DateTimeZone('UTC'));
        $endDt = clone $startDt;
        $endDt->add(new \DateInterval(sprintf('P%dD', $period)));

        $tournament = new Tournament();
        $tournament
            ->setName($name)
            ->setType($type)
            ->setStartDate($startDt)
            ->setEndDate($endDt)
        ;

        $this->addTeams($tournament);

        $this->save($tournament);
    }

    /**
     * @param User $user
     *
     * @return Tournament[]
     */
    public function findActiveByUser(User $user)
    {
        return $this
            ->getRepository()
            ->createQueryBuilder('t')
            ->join('t.teams', 'teams')
            ->join('teams.participants', 'p')
            ->join('p.user', 'user')
            ->where('p.user = :user')
            ->andWhere('t.endDate > :cur_date')
            ->setParameter('cur_date', new \DateTime('now', new \DateTimeZone('UTC')))
            ->setParameter('user', $user)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param User $user
     * @param $type
     *
     * @return Tournament
     */
    public function findActiveByTypeAndUser(User $user, $type)
    {
        return $this
            ->getRepository()
            ->createQueryBuilder('t')
            ->join('t.teams', 'teams')
            ->join('teams.participants', 'p')
            ->join('p.user', 'user')
            ->where('p.user = :user')
            ->andWhere('t.endDate > :cur_date')
            ->andWhere('t.type = :type')
            ->setParameter('cur_date', new \DateTime('now', new \DateTimeZone('UTC')))
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult()
            ;
    }

    /**
     * @param int $id
     * @param int $participantId
     *
     * @return TournamentTeamParticipant|null
     */
    public function findParticipant($id, $participantId)
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('participant')
            ->from('AppBundle:TournamentTeamParticipant', 'participant')
            ->join('participant.team', 'team')
            ->join('team.tournament', 'tournament', 'WITH', 'tournament = :id')
            ->andWhere('participant.id = :participant_id')
            ->setParameter('id', $id)
            ->setParameter('participant_id', $participantId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param int $id
     *
     * @return Tournament|null
     */
    public function findFullInfo($id)
    {
        return $this
            ->getRepository()
            ->createQueryBuilder('t')
            ->join('t.teams', 'teams')
            ->join('teams.participants', 'p')
            ->join('p.user', 'user')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param Tournament $tournament
     */
    private function addTeams(Tournament $tournament)
    {
        $users = $this->userManager->findAllOrderByTopPosition();
        $metrics = $tournament->isIndividual() ? [] : $this->metricManager->findAvailableForTeamTournaments();

        /** @var TournamentTeam[] $teams */
        $teams = [];
        foreach ($users as $user) {
            /** @var TournamentTeam|null $team */
            $team = null;
            if ($tournament->isIndividual()) {
                $team = new TournamentTeam();
                $this->createTeamValues($team, true);
            } else {
                $department = $user->getDepartment();
                if (!array_key_exists($department->getId(), $teams)) {
                    $teams[$department->getId()] = new TournamentTeam();
                    $teams[$department->getId()]->setDepartment($department);
                    $this->createTeamValues($teams[$department->getId()], false);

                    foreach ($metrics as $metric) {
                        $metricCondition = new TournamentMetricCondition();
                        $metricCondition
                            ->setMetric($metric)
                            ->setDepartment($department)
                            ->setLimit(random_int(100, 100000)) # hack for hackathon
                        ;

                        $tournament->addMetricCondition($metricCondition);
                    }
                }

                /** @var TournamentTeam $team */
                $team = $teams[$department->getId()];
            }

            $participant = $this->createParticipant($user, $tournament->isIndividual());
            $team->addParticipant($participant);

            $tournament->addTeam($team);
        }

        $this->generateRandomValuesForTournament($tournament);
    }

    private function generateRandomValuesForTournament(Tournament $tournament)
    {
        foreach ($tournament->getTeams() as $team) {
            foreach($team->getValues() as $teamValue) {
                $restValue = random_int(0, 10000);
                $teamValue->setValue($restValue);
                $participants = $team->getParticipants();
                for ($i = 0; $i < count($participants) - 1; $i++) {
                    $participant = $participants[$i];
                    $value = random_int(0, $restValue);
                    $restValue -= $value;
                    foreach($participant->getValues() as $participantValue) {
                        if ($participantValue->getMetric()->getCode() !== $teamValue->getMetric()->getCode()) {
                            continue;
                        }
                        $participantValue->setValue($value);
                        break;
                    }
                }
                foreach($participants[count($participants) - 1]->getValues() as $participantValue) {
                    if ($participantValue->getMetric()->getCode() !== $teamValue->getMetric()->getCode()) {
                        continue;
                    }
                    $participantValue->setValue($restValue);
                    break;
                }
            }
        }
    }

    /**
     * @param User $user
     * @param bool $individual
     *
     * @return TournamentTeamParticipant
     */
    private function createParticipant(User $user, $individual)
    {
        $participant = new TournamentTeamParticipant();
        $participant->setUser($user);
        $metrics = $individual
            ? $this->metricManager->findAvailableForIndividualTournaments()
            : $this->metricManager->findAvailableForTeamTournaments();
        foreach ($metrics as $metric) {
            $metricValue = new MetricValue();
            $metricValue->setMetric($metric);
            $participant->addValue($metricValue);
        }

        return $participant;
    }

    /**
     * @param TournamentTeam $team
     * @param $individual
     */
    private function createTeamValues(TournamentTeam $team, $individual)
    {
        $metrics = $individual
            ? $this->metricManager->findAvailableForIndividualTournaments()
            : $this->metricManager->findAvailableForTeamTournaments();
        foreach ($metrics as $metric) {
            $metricValue = new MetricValue();
            $metricValue->setMetric($metric);
            $team->addValue($metricValue);
        }
    }
}
