<?php


use App\DTO\TransferRequestDTO;
use App\Enums\UserTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\MerchantCannotTransferException;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\TransferCompleted;
use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use App\Services\AuthorizationExternalService;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransferService $service;
    private $mockAuthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->mockAuthService = $this->createMock(AuthorizationExternalService::class);
        $this->mockAuthService->method('authorize')->willReturn(true);

        $walletRepo = new WalletRepository();
        $transferRepo = new TransferRepository();

        $this->service = new TransferService(
            $this->mockAuthService,
            $transferRepo,
            $walletRepo
        );
    }

    /** @test */
    public function merchant_cannot_initiate_transfer()
    {
        $merchant = User::factory()->create(['type' => UserTypeEnum::MERCHANT->value]);
        $wallet = Wallet::factory()->create(['user_id' => $merchant->id, 'balance' => 10000]);

        $payee = User::factory()->create();
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0]);

        $dto = new TransferRequestDTO(
            payerId: $merchant->id,
            payeeId: $payee->id,
            amount: 5000
        );

        $this->expectException(MerchantCannotTransferException::class);

        

        $this->service->transfer($dto);
    }

    /** @test */
    public function transfer_fails_if_balance_is_insufficient()
    {
        $payer = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
        Wallet::factory()->create(['user_id' => $payer->id, 'balance' => 100]);

        $payee = User::factory()->create();
        Wallet::factory()->create(['user_id' => $payee->id, 'balance' => 0]);

        $dto = new TransferRequestDTO(
            payerId: $payer->id,
            payeeId: $payee->id,
            amount: 5000
        );

        $this->expectException(\App\Exceptions\InsufficientBalanceException::class);

        $this->service->transfer($dto);
    }

    /** @test */
    public function test_status_transacao_e_atualizado_corretamente()
    {
        Notification::fake();
        
        $payerUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
        $payeeUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
        $payerWallet = Wallet::factory()->create(['user_id' => $payerUser->id, 'balance' => 1000]);
        $payeeWallet = Wallet::factory()->create(['user_id' => $payeeUser->id, 'balance' => 5000]);

        // Success
        $dto = new TransferRequestDTO($payerUser->id, $payeeUser->id, 1);
        $transaction = $this->service->transfer($dto);
        $this->assertEquals('completed', $transaction->status);

        // Fail
        $dtoFail = new TransferRequestDTO($payerUser->id, $payeeUser->id, 100);
        try {
            $transactionFailed = $this->service->transfer($dtoFail);
        } catch (InsufficientBalanceException $e) {

            $transactionFailed = Transfer::orderBy('id', 'desc')->first();
            $this->assertEquals('failed', $transactionFailed->status);
        }
    }

    /** @test */
    public function test_transferencia_dispara_notificacoes_email_e_sms()
    {
        Notification::fake();

        $payerUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
        $payeeUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
        $payerWallet = Wallet::factory()->create(['user_id' => $payerUser->id, 'balance' => 1000]);
        $payeeWallet = Wallet::factory()->create(['user_id' => $payeeUser->id, 'balance' => 500]);

        $dto = new TransferRequestDTO($payerUser->id, $payeeUser->id, 1);
        $this->service->transfer($dto);

        Notification::assertSentTo(
            [$payeeUser],
            TransferCompleted::class
        );
    }

    /** @test */
    public function test_transfer_fails_for_unauthorized_user_type()
    {
    $payerUser = User::factory()->create(['type' => UserTypeEnum::MERCHANT->value]);
    $payeeUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
    $payer = Wallet::factory()->create(['user_id' => $payerUser->id, 'balance' => 1000]);
    $payee = Wallet::factory()->create(['user_id' => $payeeUser->id, 'balance' => 500]);

        $dto = new TransferRequestDTO($payer->id, $payee->id, 200);

        $this->expectException(MerchantCannotTransferException::class);
        $this->service->transfer($dto);

        $this->assertEquals(1000, $payer->fresh()->balance);
        $this->assertEquals(500, $payee->fresh()->balance);
    }

    /** @test */
    public function test_transfer_fails_due_to_insufficient_balance()
    {
    $payerUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
    $payeeUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
    $payer = Wallet::factory()->create(['user_id' => $payerUser->id, 'balance' => 100]);
    $payee = Wallet::factory()->create(['user_id' => $payeeUser->id, 'balance' => 500]);

        $dto = new TransferRequestDTO($payer->id, $payee->id, 200);

        $this->expectException(InsufficientBalanceException::class);
        $this->service->transfer($dto);

        $this->assertEquals(100, $payer->fresh()->balance);
        $this->assertEquals(500, $payee->fresh()->balance);
    }

    /** @test */
    public function test_successful_transfer_updates_balance_and_status_and_triggers_notifications()
    {
        Notification::fake();

        $payerUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
        $payeeUser = User::factory()->create(['type' => UserTypeEnum::COMMON->value]);
        $payer = Wallet::factory()->create(['user_id' => $payerUser->id, 'balance' => 1000]);
        $payee = Wallet::factory()->create(['user_id' => $payeeUser->id, 'balance' => 1000]);

        $dto = new TransferRequestDTO($payer->id, $payee->id, 2);
        $transaction = $this->service->transfer($dto);

        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals(800, $payer->fresh()->balance);
        $this->assertEquals(1200, $payee->fresh()->balance);

        Notification::assertSentTo(
            [$payeeUser],
            TransferCompleted::class
        );
    }
}
