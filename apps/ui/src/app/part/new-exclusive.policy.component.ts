import {Component, EventEmitter, Output} from '@angular/core';
import {ExclusivePolicy} from "../shared/exclusive-policy";

@Component({
  selector: 'new-exclusive-policy',
  templateUrl: './new-exclusive.policy.component.html',
})
export class NewExclusivePolicyComponent {
    @Output() onExclusivePolicyAdded = new EventEmitter<ExclusivePolicy>();

    policy: ExclusivePolicy;

    constructor() {
        this.policy = new ExclusivePolicy;
        this.policy.onDuplicateRun = 'mark_job_as_canceled';
    }

    add() {
        this.onExclusivePolicyAdded.emit(this.policy);
    }
}
