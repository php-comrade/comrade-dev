import {Component, EventEmitter, Output} from '@angular/core';
import {Runner} from "../shared/runner";
import {QueueRunner} from "../shared/queue-runner";

@Component({
  selector: 'runner-new-queue',
  template: `
      <div class="form-group">
          <label for="queue">Queue:</label>
          <input type="text" class="form-control" id="queue" required [(ngModel)]="queue" name="queue">
      </div>

      <div class="form-group">
          <label for="connection_dsn">Connection DSN:</label>
          <input type="text" class="form-control" id="connection_dsn" [(ngModel)]="connectionDsn" name="connection_dsn">
      </div>

      <a href="javascript:void(0)" (click)="addRunner()">Add</a>
  `
})
export class RunnerNewQueueComponent {
    @Output() onRunnerAdded = new EventEmitter<Runner>();

    queue: string;
    connectionDsn: string;

    addRunner() {
        let runner = new QueueRunner();
        runner.queue = this.queue;
        runner.connectionDsn = this.connectionDsn;

        this.onRunnerAdded.emit(runner);
    }
}
