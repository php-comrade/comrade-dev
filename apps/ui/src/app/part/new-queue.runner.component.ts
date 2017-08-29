import {Component, EventEmitter, Input, Output} from '@angular/core';
import {Runner} from "../shared/runner";
import {QueueRunner} from "../shared/queue-runner";

@Component({
  selector: 'new-queue-runner',
  templateUrl: './new-queue.runner.component.html',
})
export class NewQueueRunnerComponent {
    @Output() onRunnerAdded = new EventEmitter<Runner>();

    queue: string;
    connectionDsn: string;

    addRunner() {
        let queueRunner = new QueueRunner();
        queueRunner.queue = this.queue;
        queueRunner.connectionDsn = this.connectionDsn;

        this.onRunnerAdded.emit(queueRunner);
    }
}
