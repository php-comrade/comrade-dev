import {Component, EventEmitter, Input, Output} from '@angular/core';
import {Trigger} from "../shared/trigger";

@Component({
  selector: 'list-triggers',
  templateUrl: './list.triggers.component.html',
})
export class ListTriggersComponent {
    @Input() triggers: Trigger[];
    @Input() showRemove: false;
    @Output() onRemoveTrigger = new EventEmitter<Trigger>();

    isCronTrigger(trigger: Trigger): boolean {
        return trigger.schema == 'http://comrade.forma-pro.com/schemas/trigger/CronTrigger.json';
    }

    isSimpleTrigger(trigger: Trigger): boolean {
        return trigger.schema == 'http://comrade.forma-pro.com/schemas/trigger/SimpleTrigger.json';
    }

    removeTrigger(trigger: Trigger) {
        this.onRemoveTrigger.emit(trigger);
    }
}
