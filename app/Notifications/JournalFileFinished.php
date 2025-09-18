<?php

namespace App\Notifications;

use App\Models\Journal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JournalFileFinished extends Notification implements ShouldQueue
{
    use Queueable;

    public array $journalData;
    public string $filePath;


    /**
     * Create a new notification instance.
     */
    public function __construct(array $journalData, string $filePath)
    {
        $this->journalData = $journalData;
        $this->filePath = $filePath;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $monthName = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $month = $monthName[$this->journalData['month']] ?? 'Bulan tidak diketahui';
        
        return [
            'title' => 'File Jurnal Siap Didownload',
            'message' => "Laporan jurnal untuk bulan {$month} sudah siap diunduh.",
            'file_url' => asset('storage/journals/' . basename($this->filePath)),
            'file_name' => basename($this->filePath),
            'created_at' => now(),
        ];
    }
}
