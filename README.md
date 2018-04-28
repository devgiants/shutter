# Shutter

## Usage
```php
use Devgiants\MosquittoClientsReactWrapper\Client\MosquittoClientsReactWrapper;
use Devgiants\FilesystemGPIO\Model\Board\Board;
use Devgiants\FilesystemGPIO\Model\GPIO\Logic;
use Devgiants\Shutter\Shutter;

require_once 'vendor/autoload.php';

define('COMPLETE_SHUTTER_MOVEMENT_DURATION', 10);

try {
	$board = Board::create();
	$mqttClient = MosquittoClientsReactWrapper::create( $board->getLoop() );

	// Shutter 1
	$gpo1Open  = $board->registerGPO( 203, Logic::ACTIVE_LOW );
	$gpo1Close = $board->registerGPO( 198, Logic::ACTIVE_LOW );
	$gpi1Open  = $board->registerGPI( 200, Logic::ACTIVE_LOW );
	$gpi1Close = $board->registerGPI( 199, Logic::ACTIVE_LOW );



	$shutter1 = Shutter::create(
		$gpi1Open,
		$gpi1Close,
		$gpo1Open,
		$gpo1Close,
		COMPLETE_SHUTTER_MOVEMENT_DURATION,
		$board->getLoop(),
		$mqttClient,
		'actuator/parent_room/shutter/1'
	);
} catch ( \Exception $exception ) {
	echo "{$exception->getCode()} - {$exception->getMessage()}";
}
```