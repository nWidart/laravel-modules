<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\tests\BaseTestCase;

class GenerateNotificationCommandTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_the_mail_class()
    {
        $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Notifications/WelcomeNotification.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-notification', ['name' => 'WelcomeNotification', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Notifications/WelcomeNotification.php');

        $this->assertEquals($this->expectedContent(), $file);
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed \$notifiable
     * @return array
     */
    public function via(\$notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed \$notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(\$notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', 'https://laravel.com')
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed \$notifiable
     * @return array
     */
    public function toArray(\$notifiable)
    {
        return [
            //
        ];
    }
}

TEXT;
    }
}
