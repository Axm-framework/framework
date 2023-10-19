namespace {namespace};

use App\Controllers\BaseController;

class {class} extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Return view
    * @return mixed
    */
    public function index()
    {
        view('pages.welcome');
    }
}