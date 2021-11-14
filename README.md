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

