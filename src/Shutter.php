<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 27/04/18
 * Time: 14:17
 */
namespace Devgiants\Shutter;

use Devgiants\FilesystemGPIO\Model\GPIO\GPI;
use Devgiants\FilesystemGPIO\Model\GPIO\GPO;
use Devgiants\MosquittoClientsReactWrapper\Client\MosquittoClientsReactWrapper;
use React\EventLoop\LoopInterface;

class Shutter {

	const OPEN = 'open';

	const CLOSE = 'close';
	/**
	 * @var GPI $inputOpen
	 *
	 */
	protected $inputOpen;

	/**
	 * @var GPI $inputClose
	 *
	 */
	protected $inputClose;

	/**
	 * @var GPO $outputOpen
	 *
	 */
	protected $outputOpen;

	/**
	 * @var GPO $outputClose
	 *
	 */
	protected $outputClose;

	/**
	 * @var int $completeMovementDuration
	 */
	protected $completeMovementDuration;

	/**
	 * @var LoopInterface $loop
	 */
	protected $loop;

	/**
	 * @var MosquittoClientsReactWrapper $mqttClient
	 */
	protected $mqttClient;


	/**
	 * @param GPI $inputOpen
	 * @param GPI $inputClose
	 * @param GPO $outputOpen
	 * @param GPO $outputClose
	 * @param int $completeMovementDuration
	 * @param LoopInterface $loop
	 * @param MosquittoClientsReactWrapper $mqttClient
	 *
	 * @return static
	 */
	public static function create(
		GPI $inputOpen,
		GPI $inputClose,
		GPO $outputOpen,
		GPO $outputClose,
		int $completeMovementDuration,
		LoopInterface $loop,
		MosquittoClientsReactWrapper $mqttClient
	) {
		// TODO checks
		return new static(
			$inputOpen,
		$inputClose,
		$outputOpen,
		$outputClose,
		$completeMovementDuration,
		$loop,
		$mqttClient
		);
	}

	/**
	 * Shutter constructor.
	 *
	 * @param GPI $inputOpen
	 * @param GPI $inputClose
	 * @param GPO $outputOpen
	 * @param GPO $outputClose
	 * @param int $completeMovementDuration
	 * @param LoopInterface $loop
	 * @param MosquittoClientsReactWrapper $mqttClient
	 */
	private function __construct(
		GPI $inputOpen,
		GPI $inputClose,
		GPO $outputOpen,
		GPO $outputClose,
		int $completeMovementDuration,
		LoopInterface $loop,
		MosquittoClientsReactWrapper $mqttClient
	) {
		$this->inputOpen = $inputOpen;
		$this->inputClose = $inputClose;
		$this->outputOpen = $outputOpen;
		$this->outputClose = $outputClose;
		$this->completeMovementDuration = $completeMovementDuration;
		$this->loop = $loop;
		$this->mqttClient = $mqttClient;
	}
}