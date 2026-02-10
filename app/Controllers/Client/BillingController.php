<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use Core\Controller;

/**
 * Client Billing Controller
 *
 * Manages billing, subscription info, and plan changes for the current tenant.
 */
class BillingController extends Controller
{
    public function index(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];
        $tenantData = tenant();

        // Current plan
        $plan = null;
        if (!empty($tenantData['plan_id'])) {
            $stmt = $db->prepare("SELECT * FROM plans WHERE id = :id");
            $stmt->execute(['id' => (int) $tenantData['plan_id']]);
            $plan = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        }

        // Current subscription
        $subscription = null;
        $stmt = $db->prepare(
            "SELECT * FROM subscriptions WHERE tenant_id = :tid AND status IN ('active','trialing') ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['tid' => $tenantId]);
        $subscription = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        // Payment history
        $stmt = $db->prepare(
            "SELECT * FROM payments WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT 20"
        );
        $stmt->execute(['tid' => $tenantId]);
        $payments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Available plans
        $stmt = $db->prepare("SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC");
        $stmt->execute();
        $plans = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Usage stats
        $formsCount = 0;
        $entriesThisMonth = 0;
        $usersCount = 0;

        $stmt = $db->prepare("SELECT COUNT(*) FROM forms WHERE tenant_id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $formsCount = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM entries WHERE tenant_id = :tid AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $stmt->execute(['tid' => $tenantId]);
        $entriesThisMonth = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE tenant_id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $usersCount = (int) $stmt->fetchColumn();

        return $this->view('client/billing/index', [
            'tenant'          => $tenantData,
            'plan'            => $plan,
            'subscription'    => $subscription,
            'payments'        => $payments,
            'plans'           => $plans,
            'formsCount'      => $formsCount,
            'entriesThisMonth' => $entriesThisMonth,
            'usersCount'      => $usersCount,
        ], 'layouts.client');
    }

    public function subscribe(): string
    {
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $interval = in_array($_POST['interval'] ?? '', ['monthly', 'yearly'], true)
            ? $_POST['interval']
            : 'monthly';

        if ($planId <= 0) {
            return $this->redirect('/dashboard/billing', ['error' => 'Selecione um plano valido.']);
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM plans WHERE id = :id AND is_active = 1");
        $stmt->execute(['id' => $planId]);
        $plan = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$plan) {
            return $this->redirect('/dashboard/billing', ['error' => 'Plano nao encontrado.']);
        }

        $tenantId = (int) tenant()['id'];
        $amount = $interval === 'yearly' ? (float) $plan['price_yearly'] : (float) $plan['price_monthly'];

        // Create subscription record
        $db->prepare(
            "INSERT INTO subscriptions (tenant_id, plan_id, amount, currency, `interval`, status, current_period_start, current_period_end, created_at, updated_at)
             VALUES (:tid, :pid, :amount, :currency, :interval, 'active', NOW(), DATE_ADD(NOW(), INTERVAL :months MONTH), NOW(), NOW())"
        )->execute([
            'tid'      => $tenantId,
            'pid'      => $planId,
            'amount'   => $amount,
            'currency' => $plan['currency'] ?? 'BRL',
            'interval' => $interval,
            'months'   => $interval === 'yearly' ? 12 : 1,
        ]);

        // Update tenant
        $db->prepare(
            "UPDATE tenants SET plan_id = :pid, subscription_status = 'active', updated_at = NOW() WHERE id = :tid"
        )->execute([
            'pid' => $planId,
            'tid' => $tenantId,
        ]);

        return $this->redirect('/dashboard/billing', [
            'success' => "Assinatura do plano {$plan['name']} ativada com sucesso!",
        ]);
    }

    public function cancel(): string
    {
        $db       = $this->db();
        $tenantId = (int) tenant()['id'];

        $db->prepare(
            "UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW()
              WHERE tenant_id = :tid AND status IN ('active','trialing')"
        )->execute(['tid' => $tenantId]);

        $db->prepare(
            "UPDATE tenants SET subscription_status = 'cancelled', updated_at = NOW() WHERE id = :tid"
        )->execute(['tid' => $tenantId]);

        return $this->redirect('/dashboard/billing', [
            'success' => 'Assinatura cancelada. Voce ainda tem acesso ate o fim do periodo atual.',
        ]);
    }
}
