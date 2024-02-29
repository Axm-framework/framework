
use Http\Router as Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/


Route::get('/home', function () {
    echo 'HOLa Mundo';
});

Route::get('/welcome', App\Raxm\WelcomeComponent::class);

Route::get('/auth-redirect/{provider:\w+}', [App\Raxm\AuthComponent::class, 'handlerAuthRedirect']);

Route::group('/login', function () {
    Route::get('/ruta1', function () {
        echo ' Lógica de la ruta 1';
    });

    Route::get('/ruta2', function () {
        echo ' Lógica de la ruta 2';
    });
});
