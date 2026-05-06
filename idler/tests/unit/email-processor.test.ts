import { EmailProcessorService } from '../../src/services/email-processor.service';
import { IQueueClient } from '../../src/interfaces/IQueueClient';
import { IHttpClient } from '../../src/interfaces/IHttpClient';
import { IEmailParser } from '../../src/interfaces/IEmailParser';
import { ParsedMail } from 'mailparser';

describe('EmailProcessorService', () => {
  let mockQueueClient: jest.Mocked<IQueueClient>;
  let mockHttpClient: jest.Mocked<IHttpClient>;
  let mockEmailParser: jest.Mocked<IEmailParser>;
  let processor: EmailProcessorService;

  beforeEach(() => {
    mockQueueClient = {
      push: jest.fn(),
      consume: jest.fn(),
      ack: jest.fn(),
      nack: jest.fn(),
    };
    mockHttpClient = {
        get: jest.fn(),
        post: jest.fn(),
    };
    mockEmailParser = {
        parse: jest.fn(),
    };
    
    processor = new EmailProcessorService(mockQueueClient, mockHttpClient, mockEmailParser);
  });

  it('should ACK the message when processing succeeds', async () => {
    // Mock successful parsing
    mockEmailParser.parse.mockResolvedValue({
        from: { value: [{ address: 'sender@test.com' }], text: 'sender@test.com' },
        subject: 'Test Subject'
    } as unknown as ParsedMail);

    await processor.startProcessing();

    const mockMsg = { content: Buffer.from(JSON.stringify({ account_email: 'test@test.com', raw_email: 'Raw Email Content' })) };
    const consumeCallback = mockQueueClient.consume.mock.calls[0][1];
    
    await consumeCallback(mockMsg);

    expect(mockQueueClient.ack).toHaveBeenCalledWith(mockMsg);
    expect(mockQueueClient.nack).not.toHaveBeenCalled();
  });
});
