import { AccountSyncService } from '../../src/services/account-sync.service';
import { IQueueClient } from '../../src/interfaces/IQueueClient';
import { AccountManagerService } from '../../src/services/account-manager.service';

describe('AccountSyncService', () => {
  let mockQueueClient: jest.Mocked<IQueueClient>;
  let mockManager: jest.Mocked<AccountManagerService>;
  let service: AccountSyncService;

  beforeEach(() => {
    mockQueueClient = {
      push: jest.fn(),
      consume: jest.fn(),
      ack: jest.fn(),
      nack: jest.fn(),
    };
    mockManager = {
        fetchAndStartWorkers: jest.fn(),
        shutdown: jest.fn()
    } as any;
    
    service = new AccountSyncService(mockQueueClient, mockManager);
  });

  it('should trigger sync when account_created event is received', async () => {
    await service.startListening();
    
    const mockMsg = { content: Buffer.from(JSON.stringify({ action: 'account_created', account_id: '1' })) };
    const consumeCallback = mockQueueClient.consume.mock.calls[0][1];
    
    await consumeCallback(mockMsg);

    expect(mockManager.fetchAndStartWorkers).toHaveBeenCalled();
    expect(mockQueueClient.ack).toHaveBeenCalledWith(mockMsg);
  });
});
