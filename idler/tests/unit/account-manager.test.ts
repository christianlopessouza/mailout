import { AccountManagerService } from '../../src/services/account-manager.service';
import { AccountFetcherService } from '../../src/services/account-fetcher.service';
import { ImapIdleWorker } from '../../src/workers/imap-idle.worker';
import { AccountConfig } from '../../src/types/account.types';

describe('AccountManagerService', () => {
  let mockFetcher: jest.Mocked<AccountFetcherService>;
  let mockWorker: jest.Mocked<ImapIdleWorker>;
  let manager: AccountManagerService;

  beforeEach(() => {
    mockFetcher = {
        fetchActiveAccounts: jest.fn(),
        refreshAccounts: jest.fn()
    } as any;
    
    mockWorker = {
        connect: jest.fn(),
        disconnect: jest.fn(),
        isConnected: jest.fn(),
        on: jest.fn(),
        emit: jest.fn()
    } as any;

    const workerFactory = jest.fn().mockReturnValue(mockWorker);

    manager = new AccountManagerService(mockFetcher, workerFactory);
  });

  it('should start workers for new accounts', async () => {
    const accounts: AccountConfig[] = [{ id: '1', email: 'a@test.com', password: 'p', host: 'h', port: 993, secure: true }];
    mockFetcher.fetchActiveAccounts.mockResolvedValue(accounts);

    await manager.fetchAndStartWorkers();

    expect(mockWorker.connect).toHaveBeenCalled();
  });
});
