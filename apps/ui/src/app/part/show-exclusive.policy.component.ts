import {Component, EventEmitter, Input, Output} from '@angular/core';
import {ExclusivePolicy} from "../shared/exclusive-policy";

@Component({
  selector: 'show-exclusive-policy',
  template: `
      <p>
          Job will be executed exclusively (only one at the same time). Duplicates are {{ policy.onDuplicateRun }}
          <a (click)="remove(policy)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class ShowExclusivePolicyComponent {
    @Input() policy: ExclusivePolicy;
    @Output() onRemovePolicy = new EventEmitter<ExclusivePolicy>();

    remove(policy: ExclusivePolicy) {
        this.onRemovePolicy.emit(policy);
    }
}
