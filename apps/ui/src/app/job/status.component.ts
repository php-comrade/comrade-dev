import { Component, Input } from '@angular/core';

class Status {
    id: number;
    title: string;
}

const STATUSES: Status[] = [
    { id: 1, title: 'New' },
    { id: 2, title: 'Running' },
    { id: 2 | 512, title: 'Run exclusive' },
    { id: 2 | 16, title: 'Running sub jobs' },
    { id: 2 | 256, title: 'Run sub jobs' },
    { id: 4, title: 'Done' },
    { id: 4 | 8, title: 'Canceled' },
    { id: 4 | 32, title: 'Completed' },
    { id: 4 | 64, title: 'Failed' },
    { id: 4 | 128, title: 'Terminated' }
];

@Component({
  selector: 'job-status',
  template: `Fuck {{ status }}`,
})
export class JobStatusComponent {
    @Input() status: number;


}
