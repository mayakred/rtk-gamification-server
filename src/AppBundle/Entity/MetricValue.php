<?php

/**
 * Created by IntelliJ IDEA.
 * User: Ivan Kalita
 * Date: 24.09.16
 * Time: 17:33.
 */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class MetricValue.
 *
 * @ORM\Entity()
 * @ORM\Table(name="app__metric_values")
 */
class MetricValue
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Metric
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Metric")
     * @ORM\JoinColumn(name="metric_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $metric;

    /**
     * @var TournamentTeam
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\TournamentTeam", inversedBy="values")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $team;

    /**
     * @var TournamentTeamParticipant
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\TournamentTeamParticipant", inversedBy="values")
     * @ORM\JoinColumn(name="participant_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $participant;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     */
    protected $value;

    public function __construct()
    {
        $this->value = 0;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Metric
     */
    public function getMetric()
    {
        return $this->metric;
    }

    /**
     * @param Metric $metric
     *
     * @return $this
     */
    public function setMetric($metric)
    {
        $this->metric = $metric;

        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param float $value
     *
     * @return $this
     */
    public function add($value)
    {
        $this->value += $value;

        return $this;
    }

    /**
     * @param float $value
     *
     * @return $this
     */
    public function sub($value)
    {
        $this->value -= $value;

        return $this;
    }

    /**
     * @return TournamentTeam
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param TournamentTeam $team
     *
     * @return $this
     */
    public function setTeam($team)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * @return TournamentTeamParticipant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * @param TournamentTeamParticipant $participant
     *
     * @return $this
     */
    public function setParticipant($participant)
    {
        $this->participant = $participant;

        return $this;
    }
}
