import {Component, EventEmitter, Input, Output} from '@angular/core';
import {Runner} from "../shared/runner";

@Component({
  selector: 'runner-list',
  template: `
      <p *ngIf="isQueueRunner(runner)">
          Run by sending job to queue: {{ runner.queue }}
          <a (click)="remove(runner)" href="javascript:void(0)">Remove</a>
      </p>
      <p *ngIf="isHttpRunner(runner)">
          Run by sending job by sending HTTP request to : {{ runner.url }}
          <a (click)="remove(runner)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class RunnerListComponent {
    @Input() runner: Runner;
    @Output() onRemoveRunner = new EventEmitter<Runner>();

    isQueueRunner(runner: Runner): boolean {
        return runner.schema == 'http://comrade.forma-pro.com/schemas/runner/QueueRunner.json';
    }

    isHttpRunner(runner: Runner): boolean {
      return runner.schema == 'http://comrade.forma-pro.com/schemas/runner/HttpRunner.json';
    }

    remove(runner: Runner) {
        this.onRemoveRunner.emit(runner);
    }
}
