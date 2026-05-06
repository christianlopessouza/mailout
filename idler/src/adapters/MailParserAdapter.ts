import { simpleParser, ParsedMail } from 'mailparser';
import { IEmailParser } from '../interfaces/IEmailParser';

export class MailParserAdapter implements IEmailParser {
  async parse(rawEmail: string): Promise<ParsedMail> {
    return await simpleParser(rawEmail);
  }
}
