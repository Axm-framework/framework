namespace {namespace};

use Model\Model;

/**
* Modelo {class}
*
* @category App
* @package Models
*/
class {class} extends Model
{
    /**
     * The database table used by the model.
     */
    protected $table = '{table}';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The data format to return on model retrieval.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['is_admin'];

}
