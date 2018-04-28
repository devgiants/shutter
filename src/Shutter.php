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
use React\EventLoop\Timer\TimerInterface;

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
	 * @var string
	 */
	protected $mqttTopic;

	/**
	 * @var TimerInterface
	 */
	protected $timer;


	/**
	 * @param GPI $inputOpen
	 * @param GPI $inputClose
	 * @param GPO $outputOpen
	 * @param GPO $outputClose
	 * @param int $completeMovementDuration
	 * @param LoopInterface $loop
	 * @param MosquittoClientsReactWrapper $mqttClient
	 * @param string $mqttTopic
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
		MosquittoClientsReactWrapper $mqttClient,
		string $mqttTopic
	): Shutter {
		// TODO checks
		$shutter = new static(
			$inputOpen,
			$inputClose,
			$outputOpen,
			$outputClose,
			$completeMovementDuration,
			$loop,
			$mqttClient,
			$mqttTopic
		);

		return $shutter;
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
	 * @param string $mqttTopic
	 */
	private function __construct(
		GPI $inputOpen,
		GPI $inputClose,
		GPO $outputOpen,
		GPO $outputClose,
		int $completeMovementDuration,
		LoopInterface $loop,
		MosquittoClientsReactWrapper $mqttClient,
		string $mqttTopic
	) {
		$this->inputOpen                = $inputOpen;
		$this->inputClose               = $inputClose;
		$this->outputOpen               = $outputOpen;
		$this->outputClose              = $outputClose;
		$this->completeMovementDuration = $completeMovementDuration;
		$this->loop                     = $loop;
		$this->mqttClient               = $mqttClient;
		$this->mqttTopic                = $mqttTopic;
	}


	protected function registerMqtt(): void {

		$this->mqttClient->subscribe( $this->mqttTopic, function ( $message ) {
			$message = trim( $message );
			if ( static::OPEN === $message ) {
				$this->outputClose->reset();
				$this->outputOpen->set();

				$this->timer = $this->loop->addTimer( $this->completeMovementDuration, function () {
					$this->outputOpen->set();
				} );
			} else if ( static::CLOSE === $message ) {
				$this->outputOpen->reset();
				$this->outputClose->set();

				//				setTimerForEnding($board->getLoop(), $gpoClose);
				//				/*                $board->getLoop()->addTimer(COMPLETE_SHUTTER_MOVEMENT_DURATION, function () use ($gpoClose) {
				//										$gpoClose->reset();
				//								});*/
			}
		} );

	}
}