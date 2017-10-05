import {Component, Input, OnChanges, SimpleChanges} from '@angular/core';

class Status {
    id: string;
    title: string;
    badgeModifier: string;

    constructor(id: string, title: string, badgeModifier: string) {
        this.id = id;
        this.title = title;
        this.badgeModifier = badgeModifier;
    }
}

const statuses = [
  new Status('new', 'New', 'badge-default'),
  new Status('running', 'Running', 'badge-info'),
  new Status('running_sub_jobs', 'Running sub jobs', 'badge-info'),
  new Status('retrying', 'Retrying', 'badge-info'),
  new Status('canceled', 'Cancelled', 'badge-default'),
  new Status('completed', 'Completed', 'badge-success'),
  new Status('failed', 'Failed', 'badge-danger'),
  new Status('terminated', 'Terminated', 'badge-default'),
];

@Component({
  selector: 'job-status',
  template: `<span class="w-100 text-center badge {{ statusObj.badgeModifier }}">{{ statusObj.title }}</span>`,
})
export class JobStatusComponent implements OnChanges {
    @Input() status: string;
    statusObj: Status;

    ngOnChanges(changes: SimpleChanges): void {
        let currentStatus: Status = statuses.filter((status: Status) => status.id === this.status).shift();

        if (!currentStatus) {
          currentStatus = new Status(this.status, this.status, 'badge-default');
        }

        this.statusObj = currentStatus;
    }
}
