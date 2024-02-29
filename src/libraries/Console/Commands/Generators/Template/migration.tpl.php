use Database;
use Illuminate\Database\Schema\Blueprint;

return new class extends Database
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        self::db()::schema()->create('{table}', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->unique();
            $table->string('email')->unique();
            $table->string('name')->default('');
            $table->string('address')->default('');
            $table->string('country')->default('');
            $table->string('province')->default('');
            $table->string('zip_code')->default('');
            $table->string('password')->default('');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        self::db()::schema()->dropIfExists('{table}');
    }
};
