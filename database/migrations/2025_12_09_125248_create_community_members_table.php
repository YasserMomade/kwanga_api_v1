    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreateCommunityMembersTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('community_members', function (Blueprint $table) {
                $table->id();
                $table->uuid('community_id');
                $table->foreign('community_id')->references('id')->on('communities')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('role')->default('member');
                $table->boolean('is_active')->default(true);
                $table->timestamp('joined_at')->nullable();
                $table->unique(['community_id', 'user_id']);
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('community_members');
        }
    }
