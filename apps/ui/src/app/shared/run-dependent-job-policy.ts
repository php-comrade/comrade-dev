import {Policy} from "./policy";

export class RunDependentJobPolicy extends Policy {
  schema: string = 'http://comrade.forma-pro.com/schemas/policy/RunDependentJobPolicy.json';
  templateId: string;
  runAlways: boolean = false;
  runOnStatus: Set<string> = new Set();
}
