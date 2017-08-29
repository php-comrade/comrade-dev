import {Component, EventEmitter, Input, Output} from '@angular/core';
import {Runner} from "../shared/runner";

@Component({
  selector: 'list-runner',
  template: `
      <p *ngIf="isQueueRunner(runner)">
          Run by sending job to queue: {{ runner.queue }}
          <a (click)="remove(runner)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class ListRunnerComponent {
    @Input() runner: Runner;
    @Output() onRemoveRunner = new EventEmitter<Runner>();

    isQueueRunner(runner: Runner): boolean {
        return runner.schema == 'http://jm.forma-pro.com/schemas/runner/QueueRunner.json';
    }

    remove(runner: Runner) {
        this.onRemoveRunner.emit(runner);
    }
}
