import { ImapIdleWorker } from "../workers/imap-idle.worker";
import { AccountFetcherService } from "./account-fetcher.service";
import { AccountConfig } from "../types/account.types";

export class AccountManagerService {
    private workers: Map<string, ImapIdleWorker> = new Map();
    private accountFetcher: AccountFetcherService;
    private workerFactory: (account: AccountConfig) => ImapIdleWorker;

    constructor(
        accountFetcher: AccountFetcherService,
        workerFactory: (account: AccountConfig) => ImapIdleWorker,
    ) {
        this.accountFetcher = accountFetcher;
        this.workerFactory = workerFactory;
    }

    async fetchAndStartWorkers(): Promise<void> {
        console.log("Fetching active accounts...");
        const accounts = await this.accountFetcher.fetchActiveAccounts();

        if (accounts.length === 0) {
            console.warn("No active accounts found");
            return;
        }

        const activeAccountIds = new Set(accounts.map((acc) => acc.id));
        for (const [accountId, worker] of this.workers.entries()) {
            if (!activeAccountIds.has(accountId)) {
                console.log(`Removing worker for account ${accountId}`);
                await worker.disconnect();
                this.workers.delete(accountId);
            }
        }

        for (const account of accounts) {
            if (this.workers.has(account.id)) {
                const worker = this.workers.get(account.id)!;
                if (!worker.isConnected()) {
                    console.log(`Reconnecting worker for ${account.email}...`);
                    try {
                        await worker.connect();
                    } catch (error: any) {
                        console.error(
                            `Error reconnecting ${account.email}:`,
                            error.message,
                        );
                    }
                }
            } else {
                console.log(`Creating worker for: ${account.email}`);
                const worker = this.workerFactory(account);

                worker.on("maxReconnectAttempts", () => {
                    console.error(
                        `Maximum reconnection attempts reached for ${account.email}`,
                    );
                    this.workers.delete(account.id);
                });

                try {
                    await worker.connect();
                    this.workers.set(account.id, worker);
                    console.log(`Worker active for: ${account.email}`);
                } catch (error: any) {
                    console.error(
                        `Failed to connect ${account.email}:`,
                        error.message,
                    );
                }
            }
        }

        console.log(`Total of ${this.workers.size} active worker(s)`);
    }

    async shutdown(): Promise<void> {
        console.log("Disconnecting workers...");
        const disconnectPromises = Array.from(this.workers.values()).map(
            (worker) => worker.disconnect(),
        );
        await Promise.all(disconnectPromises);
    }
}
