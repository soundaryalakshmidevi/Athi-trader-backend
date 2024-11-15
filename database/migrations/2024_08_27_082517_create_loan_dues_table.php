use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanDuesTable extends Migration
{
    public function up()
    {
        Schema::create('loan_due', function (Blueprint $table) {
            $table->id();
            $table->string('loan_id', 250);
            $table->string('user_id', 255);
            $table->decimal('next_amount', 10, 2)->nullable(); // Decimal for due amount
            $table->decimal('pending_amount', 10, 2)->nullable(); // Nullable for paid amount
            $table->decimal('due_amount', 10, 2); // Decimal for due amount
            $table->decimal('paid_amount', 10, 2)->nullable(); // Nullable for paid amount
            $table->date('due_date');
            $table->date('paid_on')->nullable();
            $table->string('collection_by', 255)->nullable(); // Ensure it matches employee ID type
            $table->string('status')->default('unpaid'); // Added status column with default value
            $table->timestamps();

            // Uncomment these if you want to establish foreign key relationships
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('collection_by')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('loan_id')->references('id')->on('loan')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_due');
    }
}
