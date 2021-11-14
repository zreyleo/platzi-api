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
