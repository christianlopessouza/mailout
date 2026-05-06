import { ParsedMail } from 'mailparser';

export interface IEmailParser {
  parse(rawEmail: string): Promise<ParsedMail>;
}
