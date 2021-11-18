# Curso Avanzado de Laravel

## Repaso de Laravel

### clase 03: Configuracion de la base de datos

permitir la asignación masiva de una clase declarando su propiedad `protected $guarded = []`

### clase 05: configuracion de Laravel sanctum

1. se instala el paquete `composer require laravel/sanctum`.

2. Luego se puede publicar el archivo de configuración, ejecutando: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
`

3. `php artisan migrate`

4. A continuación, si se planea utilizar Sanctum para autenticar un SPA, debe agregar el middleware de Sanctum a su grupo de middleware api dentro de su archivo app/Http/Kernel.php:

```
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class Kernel extends HttpKernel 
{
    .
    .
    .

    protected $middlewareGroups = [
        'api' => [

        EnsureFrontendRequestsAreStateful::class,

        'throttle:60,1',

        \Illuminate\Routing\Middleware\SubstituteBindings::class,

        ],
    ]
}

```

5. Para comenzar a emitir tokens a los usuarios, su mel modelo debe usar el trait HasApiTokens:
```
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable

{
    use HasApiTokens, Notifiable;

    .
    .
    .
}
```

6. Para proteger nuestras rutas es tan simple como agregar un middleware.
```
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

### clase 06: autenticar con sanctum

1. Se crea un controlador para la autenticacion

```
<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserTokenController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required'
        ]);

        /** @var User $user */
        $user = User::where('email', $request->get('email'))->first();

        if (!$user || !Hash::check($request->get('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'El email no existe'
            ]);
        }

        return response()->json([
            'token' => $user->createToken($request->get('device_name'))->plainTextToken
        ]);
    }
}

```

2. se define la ruta

```
Route::post('sanctum/token', 'UserTokenController');
```

## Manejo de la base de datos

### clase 07: Capa de transfomacion API Resources

Los API Resources nos permite cambiar la respuesta antes de ser enviadas como tipo json.

1. `php artisan make:resource ProductResource`

```
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'nombre' => (string) $this->name,
            'precio' => (float) $this->price
        ];
    }
}
```

2. `php artisan make:resource ProductCollection`

```
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public $collects = ProductResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'links' => 'metadata'
        ];
    }
}
```

3. se usan así
```
class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new ProductCollection(Product::all());
    }

    .
    .
    .

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        $product = new ProductResource($product);

        return $product;
    }
}

```

### clase 09: relaciones polimórficas

las relaciones polimórficas nos permiten relacionar un modelo a más de un tipo de modelo. 

Para hacer esto en Laravel seguir estos pasos:

1. crear la tabla intermedia y después `php artisan migrate`
```
class CreateRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->float('score');
            $table->morphs('rateable');
            // $table->unsignedBigInteger('rateable_id');
            // $table->string('rateable_type');
            $table->morphs('qualifier');
            // $table->unsignedBigInteger('qualifier_id');
            // $table->string('qualifier_type');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
```

2. Crear el modelo, en este caso `Rating`. en lugar que extienda de `Model` extienda de `Pivot`.
```
use Illuminate\Database\Eloquent\Relations\Pivot;

class Rating extends Pivot
{
    protected $table = 'ratings';

    public $incrementing = true;

    public function rateable()
    {
        return $this->morphTo();
    }

    public function qualifier()
    {
        return $this->morphTo();
    }
}
```

3. se crea un `trait` para relacionar, en este caso `CanRate`
```
trait CanRate
{
    public function ratings($model = null)
    {
        $modelClass = $model ? $model : $this->getMorphClass();

        $morphToMany = $this->morphToMany(
            $modelClass,
            'qualifier',
            'ratings',
            'qualifier_id',
            'rateable_id'
        );

        $morphToMany
            ->as('rating')
            ->withTimeStamps()
            ->withPivot('score', 'rateable_type')
            ->wherePivot('rateable_type', $modelClass)
            ->wherePivot('qualifier_type', $this->getMorphClass());

        return $morphToMany;
    }

    public function rate(Model $model, float $score)
    {
        if ($this->hasRated($model)) {
            return false;
        }

        $this->ratings($model)->attach($model->getKey(), [
            'score' => $score,
            'rateable_type' => get_class($model)
        ]);

        return true;
    }

    public function unrate(Model $model): bool
    {
        if (! $this->hasRated($model)) {
            return false;
        }

        $this->ratings($model->getMorphClass())->detach($model->getKey());

        return true;
    }

    public function hasRated(Model $model)
    {
        return ! is_null($this->ratings($model->getMorphClass())->find($model->getKey()));
    }
}
```

4. otro trait `CanBeRated`
```
trait CanBeRated
{
    public function qualifiers(string $model = null)
    {
        $modelClass = $model ? (new $model)->getMorphClass() : $this->getMorphClass();

        return $this->morphToMany($modelClass, 'rateable', 'ratings', 'rateable_id', 'qualifier_id')
            ->withPivot('qualifier_type', 'score')
            ->wherePivot('qualifier_type', $modelClass)
            ->wherePivot('rateable_type', $this->getMorphClass());
    }

    public function averageRating(string $model = null): float
    {
        return $this->qualifiers($model)->avg('score') ?: 0.0;
    }
}
```

Estos trait `CanRate` y `CanBeRated` tienen como proposito ahorrar la programacion manual de la relaciona polimorfica que se haría en lo modelos `User` y `Product` ya que el primero puede ser calificador y calificado y por eso usa una programacion más compleja.

## La Terminal de Laravel

### clase 10: como crear comandos

1. `php artisan make:command SendNewsletterCommand`
```
class SendNewsletterCommand extends Command
{
    protected $signature = 'send:newsletter {emails?*}';

    protected $description = 'Envia un correo electronico';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $emails = $this->argument('emails');

        $builder = User::query()->whereNotNull('email_verified_at');

        if ($emails) {
            $builder->whereIn('email', $emails);
        }

        $count = $builder->count();


        if ($count) {
            $this->output->createProgressBar($count);

            $this->output->progressStart();

            $builder
                ->each(function (User $user) {
                    $user->notify(new NewsletterNotification());
                    $this->output->progressAdvance();
                });

            $this->output->progressFinish();

            $this->info("Se enviaron $count correos");
        }

        $this->info('No se envio nuingun correo');

        return 0;
    }
}
```

2. `composer require laravel/ui:^2.4` para laravel 7, luego `npm i && npm run dev`.

3. en el archivo `web.php` ubicado en la carpeta `routes` se escribe `Auth::routes(['verify' => true]);`.

4. `php artisan make:notification NewsletterNotification`

### clase 12: Programando Tareas

1. primero se registra los comandos en el archivo `Kernel.php`.
```
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendNewsletterCommand::class,
        SendVerficationEmailCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')
            ->evenInMaintenanceMode()
            ->sendOutputTo(storage_path('inspire.log'), true)
            ->everyMinute();

        $schedule->call(function () {
            echo "hola";
        })->everyFiveMinutes();

        $schedule->command(SendNewsletterCommand::class)
            ->withoutOverlapping() // evitar superposicion de tareas
            ->onOneServer()
            ->mondays();

        $schedule->command(SendVerficationEmailCommand::class)
            ->onOneServer()
            ->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
```

2. Se recomienda aprender a configurar tareas automaticas en el servidor. palabras claves: `contrab`, `cron`

## Eventos y tareas de Laravel

### clase 13: Eventos y Listeners en Laravel

1. para crear eventos `php artisan make:event NombreEvento`: 
```
class ModelRated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Model $qualifier;
    private Model $rateable;
    private float $score;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Model $qualifier, Model $rateable, float $score)
    {
        $this->qualifier = $qualifier;
        $this->rateable = $rateable;
        $this->score = $score;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    public function getQualifier()
    {
        return $this->qualifier;
    }

    public function getRateable()
    {
        return $this->rateable;
    }

    public function getScore()
    {
        return $this->score;
    }
}
```

2. crear el listener para el evento `php artisan make:listener` para que lo escuche:
```
class SendEmailModelRatedNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ModelRated $event)
    {
        $rateable = $event->getRateable();

        if ($rateable instanceof Product) {
            $notification = new ModelRatedNotification(
                $event->getQualifier()->name,
                $rateable->name,
                $event->getScore()
            );

            $rateable->createdBy->notify($notification);
        }
    }
}
```

3. se registrar en el archivo `EventServiceProvider.php`
```
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ModelRated::class => [
            SendEmailModelRatedNotification::class
        ]
    ];

    .
    .
    .
}
```

4. y se usan con el helper `event()` donde se quiere emitir el evento:
```
trait CanRate
{
    .
    .
    .

    public function rate(Model $model, float $score)
    {
        if ($this->hasRated($model)) {
            return false;
        }

        $this->ratings($model)->attach($model->getKey(), [
            'score' => $score,
            'rateable_type' => get_class($model)
        ]);

        event(new ModelRated($this, $model, $score));

        return true;
    }

    .
    .
    .
}
```

### clase 14: eventos de eloquent

los eventos de eloquent nos sirve para realizar operaciones cuando los modelos realizan ciertas acciones.
```
class Product extends Model
{
    .
    .
    .

    protected static function booted()
    {
        static::creating(function (Product $product) {
            $faker = \Faker\Factory::create();
            $product->image_url = $faker->imageUrl();
            $product->createdBY()->associate(auth()->user());
        });

    }

    .
    .
    .
}

```

pero tambien se pueden hacer observables, leer documentacion.

### clase 15: introduccion a queues y jobs

los queues y jobs ayudan al servidor hacer tareas en segundo plano

1. `php artisan queue:table`

2. se escribe la variable de entorno `QUEUE_CONNECTION=database`

3. `php artisan make:job SendWelcomeMailJob`, y se trabaja que es lo que se quiere hacer para que no interrumpa la respuesta en el servidor.
```
class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $userEmail;

    public function __construct(string $userEmail)
    {
        $this->userEmail = $userEmail;
    }

    public function handle()
    {
        $mail = new WelcomeMail();

        Mail::to($this->userEmail)->send($mail);
    }
}
```

4. se usa el helper `dispatch()` para almacenar el job donde queriamos que ejecute las acciones.
```
protected function verified(Request $request)
    {
        dispatch(new SendWelcomeEmailJob($request->user()->email));
    }
```

y para ejecutar los jobs `php artisan queue:work` o `php artisan queue:listen`.

### clase 17: laravel horizon

es un manejador de jobs

1. `composer require predis/predis`

2. `composer require laravel/horizon:^4` para laravel 7 y se tiene que estar usando una version de php superior a la 7.2.35 pero menos a la 8.

3. `php artisan horizon:install`

4. `php artisan horizon`

## Manejo de errores

### clase 18: como capturar y leer errores

la clase `Handler` ubicada en el archivo `app/Execeptions/Handler.php` se encarga de manejar los errores de la aplicacion.

### clase 19: excepciones personalizadas

1. `php artisan make:exception InvalidScoreException`
```
class InvalidScoreException extends Exception
{
    private int $from;
    private int $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function render()
    {
        return response()->json([
            trans('rating.invalidScore', [
                'from' => $this->from,
                'to' => $this->to
            ])
        ]);
    }
}

```

2. se crea un archivo `config/rating.php` para establecer limites para enviar el score
```
<?php

return [
    'models' => [
        'rating' => \App\Rating::class
    ],
    'from' => 1,
    'to' => 5
];
```

3. se crea un archivo en directorio `resorces/lang/en/rating.php`  para escribir el mensaje de error.
```
<?php

return [
    'invalidScore' => 'El valor debe estar entre :from y :to'
];

```  

4. se lee estas variables desde el lugar que se evalua el score, en este caso en el trait CanRate, que en caso de error lanzar la excepcion
```
public function rate(Model $model, float $score)
    {
        if ($this->hasRated($model)) {
            return false;
        }

        $from = config('rating.from');
        $to = config('rating.to');

        if ($score < $from || $score > $to) {
            throw new InvalidScoreException($from, $to);
        }

        $this->ratings($model)->attach($model->getKey(), [
            'score' => $score,
            'rateable_type' => get_class($model)
        ]);

        event(new ModelRated($this, $model, $score));

        return true;
    }
```


