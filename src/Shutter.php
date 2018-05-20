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
	 * Creates one shutter with any physical button, just MQTT support
	 *
	 * @param GPO $outputOpen
	 * @param GPO $outputClose
	 * @param int $completeMovementDuration
	 * @param LoopInterface $loop
	 * @param MosquittoClientsReactWrapper $mqttClient
	 * @param string $mqttTopic
	 *
	 * @return Shutter
	 */
	public static function createWithoutButtons(
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
			$outputOpen,
			$outputClose,
			$completeMovementDuration,
			$loop,
			$mqttClient,
			$mqttTopic
		);

		// Register MQTT operations
		$shutter->registerMqtt();

		return $shutter;
	}

	/**
	 * Creates one shutter with 2 buttons (one up, one down) and MQTT support
	 *
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
	public static function createWithTwoButtons(
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
			$outputOpen,
			$outputClose,
			$completeMovementDuration,
			$loop,
			$mqttClient,
			$mqttTopic,
			$inputOpen,
			$inputClose
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
	 * @param GPO $outputOpen
	 * @param GPO $outputClose
	 * @param int $completeMovementDuration
	 * @param LoopInterface $loop
	 * @param MosquittoClientsReactWrapper $mqttClient
	 * @param string $mqttTopic
	 * @param GPI $inputOpen
	 * @param GPI $inputClose
	 */
	private function __construct(
		GPO $outputOpen,
		GPO $outputClose,
		int $completeMovementDuration,
		LoopInterface $loop,
		MosquittoClientsReactWrapper $mqttClient,
		string $mqttTopic,
		GPI $inputOpen = NULL,
		GPI $inputClose = NULL
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

				$this->setTimerForEnding( $this->outputOpen );

			} else if ( static::CLOSE === $message ) {
				$this->outputOpen->reset();
				$this->outputClose->set();

				$this->setTimerForEnding( $this->outputClose );
			}
		} );
	}

	/**
	 * Listen to GPI change for shutter opening/closing
	 * @param GPI $gpi
	 * @param GPO $linkedGpo
	 */
	protected function registerGpi( GPI $gpi, GPO $linkedGpo ): void {
		if ( NULL !== $gpi ) {
			$gpi->on( GPIO::AFTER_VALUE_CHANGE_EVENT, function () use ( $gpi, $linkedGpo ) {
				if ( $gpi->isSet() ) {
					$linkedGpo->set();
					$this->setTimerForEnding( $linkedGpo );
				} else {
					$linkedGpo->reset();
					// Trigger timer cancellation to avoid unwanted effects if numerous input trigger
					if ( NULL !== $this->timer ) {
						$this->loop->cancelTimer( $this->timer );
					}
				}
			} );
		}
	}


	/**
	 * Callback used for setting timer on operations
	 * @param GPO $gpo
	 */
	protected function setTimerForEnding( GPO $gpo ): void {
		if ( NULL !== $this->timer ) {
			$this->loop->cancelTimer( $this->timer );
		}
		$this->timer = $this->loop->addTimer( $this->completeMovementDuration, function () use ( $gpo ) {
			$gpo->reset();
		} );
	}
}