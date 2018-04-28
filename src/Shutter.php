<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 27/04/18
 * Time: 14:17
 */

namespace Devgiants\Shutter;

use Devgiants\FilesystemGPIO\Model\GPIO\GPI;
use Devgiants\FilesystemGPIO\Model\GPIO\GPIO;
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

		// Create shutter
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

		$shutter->registerGpi(
			$shutter->inputOpen,
			$shutter->outputOpen
		);

		$shutter->registerGpi(
			$shutter->inputClose,
			$shutter->outputClose
		);

		// Register MQTT operations
		$shutter->registerMqtt();

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


	/**
	 * Handle MQTT topic registration and GPO invert with timer logic
	 */
	protected function registerMqtt(): void {
		$this->mqttClient->subscribe( $this->mqttTopic, function ( $message ) {
			$message = trim( $message );
			if ( static::OPEN === $message ) {
				$this->outputClose->reset();
				$this->outputOpen->set();

				$this->setTimerForEnding($this->outputOpen);

			} else if ( static::CLOSE === $message ) {
				$this->outputOpen->reset();
				$this->outputClose->set();

				$this->setTimerForEnding($this->outputClose);
			}
		} );
	}

	/**
	 * @param GPI $gpi
	 * @param GPO $linkedGpo
	 */
	protected function registerGpi(GPI $gpi, GPO $linkedGpo): void {
		$gpi->on(GPIO::AFTER_VALUE_CHANGE_EVENT, function() use($gpi, $linkedGpo) {
			if($gpi->isSet()) {
				$linkedGpo->set();
				$this->setTimerForEnding($linkedGpo);
			} else {
				$linkedGpo->reset();
			}
		});
	}


	/**
	 * @param GPO $gpo
	 */
	protected function setTimerForEnding(GPO $gpo) : void {
		$this->timer = $this->loop->addTimer( $this->completeMovementDuration, function () use ($gpo) {
			$gpo->reset();
		} );
	}
}