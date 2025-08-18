import { Link } from "@inertiajs/react";
import { getFullUrl } from "../../types/url";

const pageHeader = (props: any) => {
    return (
        <div className="">
            <div className="page-header">
                <h1 className="page-title">{props.titles}</h1>
                <div>
                    <ol className="breadcrumb">
                        {props.items.map((value: any, index: any) => {
                            return (
                                <li key={index} className="breadcrumb-item">
                                    <Link href={getFullUrl(value.urlHeader)}>
                                        {value.titulo}
                                    </Link>
                                </li>
                            );
                        })}
                        <li
                            className="breadcrumb-item active"
                            aria-current="page"
                        >
                            {props.active}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    );
};

export default pageHeader;
