<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add the new columns
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
        });

        // If you have existing data, you might want to split the name field
        // Uncomment and modify this section if needed:
        /*
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $nameParts = explode(' ', $user->name, 2);
            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
            ]);
        }
        */

        Schema::table('users', function (Blueprint $table) {
            // Remove the old name column
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add back the name column
            $table->string('name')->after('id');
        });

        // If you want to restore the original name format
        // Uncomment and modify this section if needed:
        /*
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $fullName = trim($user->first_name . ' ' . $user->last_name);
            DB::table('users')->where('id', $user->id)->update([
                'name' => $fullName,
            ]);
        }
        */

        Schema::table('users', function (Blueprint $table) {
            // Remove the first_name and last_name columns
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
