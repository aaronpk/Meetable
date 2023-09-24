<?php
namespace App\Http\Controllers\Setup;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Encryption\Encrypter;
use DateTime, DateTimeZone, Exception;
use Illuminate\Support\Facades\Schema;
use Artisan, DB;

use App\Helpers\HerokuS3;

class Controller extends BaseController
{

    public function setup() {
        // Check permissions of the storage folder
        if(!is_writable(__DIR__.'/../../../../storage/app/.gitignore')
          || !is_writable(__DIR__.'/../../../../storage/logs/.gitignore')) {
            return view('setup/permissions');
        }

        return view('setup/start');
    }

    public function database() {
        // Check for an existing DATABASE_URL config string and set that as the defaults
        if($url = env('DATABASE_URL')) {
            $db = parse_url($url);
            session([
                'setup.db_name' => substr($db['path'], 1),
                'setup.db_host' => $db['host'],
                'setup.db_username' => $db['user'],
                'setup.db_password' => $db['pass'],
            ]);
        }

        return view('setup/database', [
            'db_name' => session('setup.db_name'),
            'db_host' => session('setup.db_host'),
            'db_username' => session('setup.db_username'),
            'db_password' => session('setup.db_password'),
        ]);
    }

    public function test_database() {
        // Attempt to connect to the database with the provided credentials or the defined DB URL
        try {
            if(env('DATABASE_URL'))
                DB::raw('SELECT VERSION();');
            else
                $db = new \PDO('mysql:dbname='.request('db_name').';host='.request('db_host'),
                request('db_username'), request('db_password'));
            $success = true;
        } catch(\PDOException $e) {
            $success = false;
        }

        if(!$success) {

            session()->flash('setup-database-error',
                'There was an error connecting to the database. Please check the settings below and try again');

            return view('setup/database', [
                'db_name' => request('db_name'),
                'db_host' => request('db_host'),
                'db_username' => request('db_username'),
                'db_password' => request('db_password'),
            ]);

        } else {

            if(request('db_name')) {
                // Store the DB connection info in the session for later
                session([
                    'setup.db_name' => request('db_name'),
                    'setup.db_host' => request('db_host'),
                    'setup.db_username' => request('db_username'),
                    'setup.db_password' => request('db_password'),
                    'setup.db_complete' => true,
                ]);
            } else {
                session([
                    'setup.db_complete' => true
                ]);
            }

            return redirect(route('setup.app-settings'));
        }
    }

    public function app_settings() {
        // Check that DB settings are here, or go back a step
        if(!session('setup.db_complete')) {
            return redirect(route('setup.database'));
        }

        return view('setup/app-settings');
    }

    public function save_app_settings() {
        $properties = ['app_name', 'app_url', 'googlemaps_api_key'];
        foreach($properties as $p) {
            session(['setup.'.$p => request($p)]);
        }

        return redirect(route('setup.auth-method'));
    }

    public function auth_method() {
        // Check that app name is set, or go back a step
        if(!session('setup.app_name')) {
            return redirect(route('setup.app-settings'));
        }

        return view('setup/auth-method', [
            'is_heroku' => $this->is_heroku(),
        ]);
    }

    public function save_auth_method() {
        session(['setup.auth_method' => request('method')]);
        if(request('method') == 'session')
            return redirect(route('setup.save-config'));

        return redirect(route('setup.auth-settings'));
    }

    public function auth_settings() {
        // Check that app name is set, or go back a step
        if(!session('setup.app_name')) {
            return redirect(route('setup.app-settings'));
        }

        return view('setup/auth-settings');
    }

    public function register_heroku_app() {
        $hostname = parse_url(session('setup.app_url'), PHP_URL_HOST);
        if(!preg_match('/(.+)\.herokuapp\.com/', $hostname, $match))
            return response()->json(['error' => 'invalid app URL']);
        $app_name = $match[1];

        $ch = curl_init('https://meetable.org/heroku/register.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'name' => session('setup.app_name'),
            'app_name' => $app_name,
            'remote_ip' => $_SERVER['REMOTE_ADDR'],
        ]));
        $data = json_decode(curl_exec($ch), true);

        return response()->json($data);
    }

    public function save_auth_settings() {
        $properties = ['github_client_id', 'github_client_secret',
            'github_allowed_users', 'github_admin_users',
            'heroku_client_id', 'heroku_client_secret',
        ];
        foreach($properties as $p) {
            if(request($p))
                session(['setup.'.$p => request($p)]);
        }

        return redirect(route('setup.save-config'));
    }

    public function save_config() {
        $config = file_get_contents(__DIR__.'/../../../../.env.example');

        // Generate an APP_KEY
        $app_key = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        self::write_config_value($config, 'APP_KEY', $app_key);

        if(env('DATABASE_URL')) {
            // If a DATABASE_URL env is defined, don't set any database configs
            self::comment_config_value($config, 'DB_CONNECTION', '');
            self::comment_config_value($config, 'DB_HOST', '');
            self::comment_config_value($config, 'DB_DATABASE', '');
            self::comment_config_value($config, 'DB_USERNAME', '');
            self::comment_config_value($config, 'DB_PASSWORD', '');
            self::comment_config_value($config, 'DB_PORT', '');
        } else {
            self::write_config_value($config, 'DB_HOST', session('setup.db_host'));
            self::write_config_value($config, 'DB_DATABASE', session('setup.db_name'));
            self::write_config_value($config, 'DB_USERNAME', session('setup.db_username'));
            self::write_config_value($config, 'DB_PASSWORD', session('setup.db_password'));
        }

        if(!empty(env('CLOUDCUBE_URL'))) {
            self::write_config_value($config, 'FILESYSTEM_DRIVER', 's3');
            self::write_config_value($config, 'AWS_ACCESS_KEY_ID', env('CLOUDCUBE_ACCESS_KEY_ID'));
            self::write_config_value($config, 'AWS_SECRET_ACCESS_KEY', env('CLOUDCUBE_SECRET_ACCESS_KEY'));
            self::write_config_value($config, 'AWS_DEFAULT_REGION', HerokuS3::get_default_aws_region());
            self::write_config_value($config, 'AWS_BUCKET', HerokuS3::get_aws_bucket_from_env());
            self::write_config_value($config, 'AWS_ROOT', HerokuS3::get_aws_root_from_env());
        }

        self::write_config_value($config, 'AUTH_METHOD', session('setup.auth_method'));
        switch(session('setup.auth_method')) {
            case 'heroku':
                foreach(['heroku_client_id', 'heroku_client_secret'] as $k) {
                    self::write_config_value($config, strtoupper($k), session('setup.'.$k));
                }
                break;
            case 'github':
                foreach(['github_client_id', 'github_client_secret', 'github_allowed_users', 'github_admin_users'] as $k) {
                    self::write_config_value($config, strtoupper($k), session('setup.'.$k));
                }
                break;
        }

        foreach(['app_name', 'app_url'] as $k) {
            self::write_config_value($config, strtoupper($k), session('setup.'.$k));
        }

        if(self::is_heroku()) {
            self::write_config_value($config, 'QUEUE_CONNECTION', 'database');

            // Remove comments
            $heroku = preg_replace('/#.+\n/m', '', $config);

            // Remove any vars not defined
            $heroku = preg_replace('/^[A-Z_]+=\h*$/m', '', $heroku);

            // Remove blank lines
            $heroku = preg_replace('/^\h*\v+/m', '', $heroku);

            // Prefix the heroku command on each line
            $lines = explode("\n", $heroku);
            $lines = array_map(function($line){
                if($line)
                    return 'heroku config:set '.$line;
            }, $lines);
            $heroku = implode("\n", $lines);

            $heroku_app = false;
            if(preg_match('~//([^\.]+)\.herokuapp\.com~', session('setup.app_url'), $match)) {
                $heroku_app = $match[1];
            }

            session([
                'setup.heroku_config' => $heroku,
                'setup.heroku_app' => $heroku_app,
            ]);

            return view('setup/heroku-config', [
                'config' => $heroku,
                'heroku_app' => $heroku_app,
            ]);
        } else {

            // Attempt to write the .env file now, or output the contents if it can't be written
            $written = @file_put_contents(__DIR__.'/../../../../.env', $config);
            if(!$written) {
                return view('setup/config-file', [
                    'config' => $config,
                ]);
            } else {
                return view('setup/config-success');
            }
        }
    }

    public function push_heroku_config() {
        // Push the config to the configure tool and redirect there
        $ch = curl_init('https://meetable.org/heroku/configure.php');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'app_name' => session('setup.heroku_app'),
            'config' => session('setup.heroku_config'),
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = json_decode(curl_exec($ch), true);
        return redirect($data['url']);
    }

    public function heroku_config_complete() {
        return view('setup/heroku-config-complete');
    }

    public function heroku_in_progress() {
        return response()->json([
            'setup' => 'in-progress'
        ]);
    }

    public function heroku_finished() {
        return response()->json([
            'setup' => 'finished'
        ]);
    }

    // This is run after the environment is created so that the database settings are loaded
    public function create_database() {
        // Only allow this to run if the database has not already been created
        $exists = Schema::hasTable('migrations');
        if($exists) {
            return redirect('/');
        } else {
            // Run the migrations to create the tables
            Artisan::call('migrate', ['--force' => true]);
            return view('setup/database-complete');
        }
    }

    public function create_database_error() {
        // Called if the user fails to set the config vars in Heroku, or if somehow the .env file failed and they clicked continue anyway
        return view('setup/error');
    }

    public function redirect_after_complete() {
        return redirect('/');
    }

    private static function write_config_value(&$config, $key, $value) {
        if(strpos($value, ' '))
            $value = '"'.$value.'"';

        $config = preg_replace('/^'.$key.'=.*/m', $key.'='.$value, $config, 1, $count);
        // If nothing matched, then find commented out lines and uncomment them
        if($count == 0) {
            $config = preg_replace('/^# '.$key.'=.*/m', $key.'='.$value, $config, 1, $count);
        }
    }

    private static function comment_config_value(&$config, $key) {
        $config = preg_replace('/^'.$key.'=/m', '# '.$key.'=', $config, -1, $count);
    }

    public static function is_heroku() {
        // Determine whether this app is running on Heroku or not
        return isset($_ENV['DYNO']);
    }

}
