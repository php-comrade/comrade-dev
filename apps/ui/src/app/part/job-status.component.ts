import {Component, Input, OnChanges, SimpleChanges} from '@angular/core';

class Status {
    id: number;
    title: string;
    badgeModifier: string;

    constructor(id: number, title: string, badgeModifier: string) {
        this.id = id;
        this.title = title;
        this.badgeModifier = badgeModifier;
    }
}

const statuses = {
  1: new Status(1, 'New', 'badge-default'),

  // running
  2: new Status(2, 'Running', 'badge-info'),
  514: new Status(514, 'Run exclusive', 'badge-info'),
  18: new Status(18, 'Running sub jobs', 'badge-info'),
  258: new Status(258, 'Run sub jobs', 'badge-info'),

  // done
  4: new Status(4, 'Done', 'badge-success'),
  12: new Status(12, 'Cancelled', 'badge-default'),
  36: new Status(36, 'Completed', 'badge-success'),
  68: new Status(68, 'Failed', 'badge-danger'),
  132: new Status(132, 'Terminated', 'badge-default'),
};

@Component({
  selector: 'job-status',
  template: `<span class="w-100 text-center badge {{ statusObj.badgeModifier }}">{{ statusObj.title }}</span>`,
})
export class JobStatusComponent implements OnChanges {
    @Input() status: number;
    statusObj: Status;

    ngOnChanges(changes: SimpleChanges): void {
        this.statusObj = statuses[this.status];
    }
}
