import {Component, Input} from '@angular/core';
import {Runner} from "../shared/runner";

@Component({
  selector: 'list-runner',
  templateUrl: './list.runner.component.html',
})
export class ListRunnerComponent {
    @Input() runner: Runner;

    isQueueRunner(runner: Runner): boolean {
        return runner.schema == 'http://jm.forma-pro.com/schemas/runner/QueueRunner.json';
    }
}
